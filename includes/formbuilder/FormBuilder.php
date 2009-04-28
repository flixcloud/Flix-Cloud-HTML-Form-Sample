<?php

/**
 *	FormBuilder
 *	===========
 *
 *	@author	Jonathan Nicol (f6design.com, f6design.com/journal)
 *	@version 1.2
 *	@date 18 May 2007
 *
 *	FormBuilder is designed to make the task of building and validating web forms simpler.
 *	FormBuilder uses validaForms by Erick Vavretchek for form validation.
 *
 */
 
// include form validation class
require_once "validaForms.class.php";

class FormBuilder {
	
	var $fields = array();
	var $btnlabel;
	var $formname;
	var $formintro;
	var $formchecked = false;
	var $submitvals = array();
	var $formerrors = array();
/**	
 *	FormBuilder
 *	===========
 *
 *	Constructor.
 *
 *	@param string $formname The name of the form. Helps us tell forms apart if there is more then one on the page.
 *  @param string $btnlabel The label for the form submit button. Defaults to "Submit"
 *  @param string $formintro Text to introduce the form, such as: "Please enter your details"
 *  @param bool $usePseudoLegends If set to true, <h3> will replace <legend>. Defaults to false.
 *  @param bool $showDividers If set to true an additional <dd> will be placed after each field, to be styled as a divider. Defaults to false.
 * 	
 */			
	function FormBuilder($formname,$btnlabel="Submit",$formintro=null,$usePseudoLegends=false,$showDividers=false){
		$this->btnlabel = $btnlabel;
		$this->formname = $formname;
		$this->formintro = $formintro;
		$this->usePseudoLegends = $usePseudoLegends;
		$this->showDividers = $showDividers;
		// create instance of valida forms (validation) class
		$this->vFs = new validaForms;
	}
/**
 *	checkSubmit
 *	===========
 *
 *	Checks if form has been completed correctly
 *
 *	@return bool
 *
 */	
	function checkSubmit(){
		$this->formchecked = true;
		if (isset($_POST['submit'])&&isset($_POST['fbformname'])&&$_POST['fbformname']==$this->formname) {
			// form validation logic goes here...
			foreach ($this->fields as $field) {
				if ($field['type']!="fieldset"){
					$errorFound = false;
					// Use valida forms class for form field validation
					// First, convert human readable setup variables into valida form values,
					// and set default values in case  no value was specified
					$required = (isset($field['required'])&&$field['required']==true) ? 1 : 0;
					$maxlength = (isset($field['maxlength'])) ? $field['maxlength'] : null;
					$minlength = (isset($field['minlength'])) ? $field['minlength'] : 0;
					$invalidvalue = (isset($field['invalidvalue'])) ? $field['invalidvalue'] : null;
					$headerinjectioncheck = (isset($field['headerinjectioncheck'])) ? $field['headerinjectioncheck'] : null;
					if (isset($field['validationtype'])){
						switch ($field['validationtype']){
							case "alpha":
								$validationtype=1;
								break;
							case "numeric":
								$validationtype=2;
								break;
							case "alphanumeric":
								$validationtype=3;
								break;
							case "email":
								$validationtype="email";
								break;
							default:
								$validationtype=null;
						}					
					} else {
						$validationtype=null;
					}
					// now perform the validation
					if ($field['type']=="textbox"||$field['type']=="textarea"||$field['type']=="password"){
						if ($validationtype!="email"){
							if ((!$this->vFs->textBox($field['label'],$_POST[$field['id']],$required,$validationtype,$minlength,$maxlength))){
								$errorFound = true;
							}
						} else {
							if ((!$this->vFs->emailCheck($field['label'],$_POST[$field['id']],$required))){
								$errorFound = true;
							}
						}
						
					}
					if ($field['type']=="select"){
						if((!$this->vFs->selectBox($field['label'],$_POST[$field['id']],$invalidvalue))){
							$errorFound = true;
						}
					}
					if ($field['type']=="radio"&&$required==1){
						// in case no radio button was selected, set POST value to null
						if (!isset($_POST[$field['id']])){
							$_POST[$field['id']]=null;
						}
						if((!$this->vFs->radioButton($field['label'],$_POST[$field['id']]))){
							$errorFound = true;
						}
					}
					if ($field['type']=="checkbox"&&$required==1){
						// This will concatenate all checkboxes with the same name into a comma seperated list,
						// and copy the result into $_POST[$field['id']]
						$_POST[$field['id']]=null;
						if (!isset($_POST[$field['id'].'_arr'])){
							$_POST[$field['id'].'_arr']=null;
						}
						if((!$this->vFs->checkBox($field['label'],$_POST[$field['id'].'_arr']))){
							$errorFound = true;
						} else {
							// loop through all checkboxes in this 'group' and concatenate their values
							foreach ($_POST[$field['id'].'_arr'] as $value){
								$_POST[$field['id']].=$value.',';
							}
							// tidy up: strip last comma
							$_POST[$field['id']]=substr($_POST[$field['id']],0,strlen($_POST[$field['id']])-1);
						}
					}
					if ($errorFound){
						// Validation error! Push details of the error into our formerrors array for display to the user.
						array_push($this->formerrors,array('id'=>$field['id'],'errormsg'=>$this->vFs->erro));
					} else {
						// Form header injection validation
						if ($headerinjectioncheck=='full'){
							if ($this->checkHeaderInjection($_POST[$field['id']])){
								array_push($this->formerrors,array('id'=>$field['id'],'errormsg'=>'The \''.$field['label'].'\' field contains formatting that may cause errors processing this form. This may occur if you pasted text into the field from another application, or your text includes "bcc:" or "content-type:".'));
							}
						}
						if ($headerinjectioncheck=='light'){
							if ($this->checkHeaderInjectionLight($_POST[$field['id']])){
								array_push($this->formerrors,array('id'=>$field['id'],'errormsg'=>'The \''.$field['label'].'\' field contains formatting that may cause errors processing this form. This may occur if your text includes "bcc:" or "content-type:".'));
							}
						}
					}
				}
			}
			if (count($this->formerrors)>0){
				// there was one or more errors detected by valida forms
				return false;
			}
			// if we got this far, form was successfully validated!
			return true;
		} else {
			// form hasn't been submitted
			return false;
		}
	}	
/**
 *	checkHeaderInjection
 *	====================
 *
 *	Protects form fields against Header Injection attacks
 *
 *	@return bool Returns true if a header injection attack was detected
 *
 */		
	function checkHeaderInjection($value){
		if (eregi("%0A",$value) || eregi("%0D",$value) || eregi("\r",$value) || eregi("\n",$value) || eregi("bcc:",$value) || eregi("content-type:",$value)){
			return true;
		} else {
			return false;
		}
	}
/**
 *	checkHeaderInjection
 *	====================
 *
 *	Lightweight header injection protection. For fields that are allowed linebreaks,
 *  but still contain telltale header injection strings.
 *
 *	@return bool Returns true if a header injection attack was detected
 *
 */		
	function checkHeaderInjectionLight($value){
		if (eregi("bcc:",$value) || eregi("content-type:",$value)){
			return true;
		} else {
			return false;
		}
	}	
/**
 *	addFieldSet
 *	===========
 *
 *	Adds a new HTML fieldset and legend to the form
 *
 *	@param string $legend A description for the fieldset, to be rendered in a <legend> tag
 *
 */		
	function addFieldSet($legend){
		$fieldset = array(
			'type' =>'fieldset',
			'legend' => $legend
		);
		array_push($this->fields,$fieldset);
	}
/**
 *	closeFieldSet
 *	=============
 *
 *	Closes an HTML fieldset
 *
 *
 */		
	function closeFieldSet(){
		$fieldset = array(
			'type' =>'closefieldset'
		);
		array_push($this->fields,$fieldset);
	}
/**
 *	addField
 *	========
 *
 *	Adds a new HTML form field to the form
 *
 *	@param array $field An array containing all the key/value pairs describing the field
 *
 */		
	function addField($field){
		array_push($this->fields,$field);
		//echo($this->fields[0]['type']);
	}
/**
 *	forceErrorMessage
 *	=================
 *
 *	Adds a new custom error message to the errors array.
 *  Useful for if you want to do custom form validation
 *  after FormBuilder has completed its own validation,
 *  and want to display an error message to the user.
 *
 *	@param string $fieldId The name of the form field that has the error message
 *	@param string $newError The error message to be displayed to the user
 *
 */		
	function forceErrorMessage($fieldId,$newError){
		array_push($this->formerrors,array('id'=>$fieldId,'errormsg'=>$newError));
	}
/**
 *	renderErrorMessage
 *	=================
 *
 *	Loops through all form validation errors and prints them to screen.
 *  If all regular validation errors are passed then any header injection
 *  errors will be displayed.
 *
 */		
	function renderErrorMessage(){
		if (count($this->formerrors)>0){
			echo('<div>');
			echo('<h2>There\'s an error in the form</h2>');
			foreach ($this->formerrors as $error) {
				echo('<p class="error">'.$error['errormsg'].'</p>');
			}
			echo('</div>');
		}
	}
/**
 *	renderThanks
 *	===========
 *
 *	Displays a Thank You message to the user upon successful
 *  processing of the form.
 *
 */		
	function renderThanks($heading=null,$message=null,$showLink=false,$btntarg=null,$btnlabel=null){
		if ($heading==null){
			$heading = "Thank You";
		}
		if ($message==null){
			$message = "Your submission was successfully processed";
		}
		if ($btnlabel==null){
			$btnlabel = "Back";
		}
		if ($this->formSuccess()){
			echo('<div class="fbthanks">');
			echo('<h2>'.$heading.'</h2>');
			echo('<p>'.$message.'</p>');
			if ($showLink){
				if ($btntarg==null){
					$btntarg = $_SERVER['PHP_SELF'];
				}
				echo('<p><a href="'.$btntarg.'">'.$btnlabel.'</a></p>');
			}
			echo('</div>');	
		}	
	}
/**
 *	isSubmitted
 *	===========
 *
 *	Checks if form has been submitted
 *
 *	@return bool
 *
 */	
	function isSubmitted(){
		if ($this->formchecked==false){
			$this->checkSubmit();
		}
		if (isset($_POST['submit'])&&isset($_POST['fbformname'])&&$_POST['fbformname']==$this->formname) {
			return true;
		}
		return false;
	}
/**
 *	formSuccess
 *	===========
 *
 *	Checks to see if the form was subitted and successfully processed.
 *  Useful for selectively showing/hiding HTML content if form was successfully processed.
 *
 *  @return bool
 *
 *
 */		
	function formSuccess(){
		if ($this->formchecked==false){
			$this->checkSubmit();
		}
		if (isset($_POST['submit'])
			&&isset($_POST['fbformname'])
			&&$_POST['fbformname']==$this->formname
			&&count($this->formerrors)==0) {
			return true;
		}
		return false;
	}
/**
 *	emailResults
 *	============
 *
 *	Emails form variables to a pre-defined email address.
 *
 *
 */		
	function emailResults($recipientEmail,$senderEmail,$senderName,$subject,$intro){
		// Setup email sending on server:
		$mail_path = "/usr/sbin/sendmail -t -i";
		$mail_from = "me@myserver.com"; 
		ini_set("sendmail_from", $mail_from);
		ini_set("sendmail_path", $mail_path);
		// Create the message body
		$emailBody = $intro."\n\n";
		foreach ($this->fields as $field) {
			switch ($field['type']){
				case 'fieldset':
					break;
				case 'hidden':
					$emailBody .= $field['id'].": ".$_POST[$field['id']]."\n";
					break;
				default:
					$emailBody .= $field['label'].": ".$_POST[$field['id']]."\n";
					break;
			}
		}
		// Send the email:
		$headers="";
		$headers.="From: ".$senderName." <".$senderEmail.">\n";  
		$headers.="Reply-To: ".$senderName." <".$senderEmail.">\n";
		mail($recipientEmail,$subject,$emailBody,$headers);
	}
/**
 *	renderForm
 *	==========
 *
 *	Prints form to screen
 *
 */
	function renderForm(){		
		// declare local variables
		$thisFormSubmitted = false;
		$fieldsetOpen = false;
		$dlOpen = false;
		$loopCount = 0;
		if (isset($_POST['submit'])&&isset($_POST['fbformname'])&&$_POST['fbformname']==$this->formname) {
			$thisFormSubmitted = true;
		}
		// Open form
		echo('<form action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">'."\n");
		// Form introduction/instructions
		//echo('<label class="opt-label main-label">'.$this->formintro.' Fields marked <span class="req">*</span> are required.</label>'."\n");
		// Loop through all form fields
		foreach ($this->fields as $field) {
			// Declare local variables	
			$fieldErrorFound = false;
			// Special case: Fieldset
			if ($field['type']=="fieldset"){
				if ($dlOpen){
					$dlOpen = true;
				}

				echo('<fieldset class="main-set">'."\n");
				if ($this->usePseudoLegends==true){
					$legendEle = 'h3';
				} else {
					$legendEle = 'legend';
				}
				echo("\t".'<'.$legendEle.' class="main-legend">'.$field['legend'].'</'.$legendEle.'>'."\n");
				$fieldsetOpen = true;
				$dlOpen = true;
			// Special case: Close fieldset
			} else if ($field['type']=="closefieldset"){
				if ($dlOpen){
					$dlOpen = true;
				}

				$fieldsetOpen = false;
				$dlOpen = false;
			// All regular form fields
			} else if ($field['type']!="hidden"){
				// This is the first field, and it's not a fieldset, so we need to open a <dl>
				if (!$dlOpen){
					$dlOpen = true;
				}
				// Field label
				if ($field['type']=='radio'||$field['type']=='checkbox'){
					echo('<span');	
				} else {
					echo('<label class="req-label" for="'.$field['id'].'"');
				}
				if (isset($_POST['submit'])){
					foreach ($this->formerrors as $error) {
						if ($error['id']==$field['id']){
							echo(' class="fbfielderror"');
							$fieldErrorFound = true;
							break;
						}
					}
				}
				echo('>');
				echo($field['label']);
				if (isset($field['required'])&&$field['required']==true){
					if (!$fieldErrorFound){
						echo(' <span class="req">*</span>');
					} else {
						echo(' *');
					}
				}
				if ($field['type']=='radio'||$field['type']=='checkbox'){
					echo('</span>');	
				} else {
					echo('</label><br />');
				}	
				// Render Fields
				switch ($field['type']){
					// Textfield
					case "textbox":
						echo("\t\t\t".'<input id="'.$field['id'].'" name="'.$field['id'].'" class="text-med" type="text" ');
						if (isset($field['maxlength'])){
							echo('maxlength="'.$field['maxlength'].'" ');
						}
						echo('value="');
						if ($thisFormSubmitted){
							echo(@$_POST[$field['id']]);
						}
						echo('" />');
						break;
					// Password
					case "password":
						echo("\t\t\t".'<input id="'.$field['id'].'" name="'.$field['id'].'" class="fbtextfield" type="password" ');
						if (isset($field['maxlength'])){
							echo('maxlength="'.$field['maxlength'].'" ');
						}
						echo('value="');
						if ($thisFormSubmitted){
							echo(@$_POST[$field['id']]);
						}
						echo('" />');
						break;
					// Textarea
					case "textarea":
						if (isset($field['rows'])) {
							$rows = $field['rows'];
						} else {
							$rows = "5";	
						}
						if (isset($field['cols'])) {
							$cols = $field['cols'];
						} else {
							$cols = "35";	
						}
						echo("\t\t\t".'<textarea id="'.$field['id'].'" name="'.$field['id'].'" class="fbtextarea" rows="'.$rows.'" cols="'.$cols.'">');
						if ($thisFormSubmitted){
							echo(@$_POST[$field['id']]);
						}
						echo('</textarea>');
						break;
					// Select
					case "select":
						echo("\t\t\t".'<select id="'.$field['id'].'" name="'.$field['id'].'" class="select">'."\n");
						for ($n=0; $n<count($field['optionlabels']);$n++) {
							echo("\t\t\t\t".'<option value="'.$field['optionvalues'][$n].'"');
							if ($thisFormSubmitted){
								if ($_POST[$field['id']]==$field['optionvalues'][$n]){
									echo(' selected="selected"');
								}
							} else if(isset($field['defaultvalue'])&&$field['optionvalues'][$n]==$field['defaultvalue']) {
								echo(' selected="selected"');
							}
							echo('>'.$field['optionlabels'][$n].'</option>'."\n");
						}
						echo("\t\t\t".'</select>'."\n");
						break;
					// Radio buttons
					case "radio":
						for ($n=0; $n<count($field['radiolabels']);$n++) {
							echo("\t\t\t".'<label>'."\n");
							echo("\t\t\t\t".'<input name="'.$field['id'].'" class="fbradio" type="radio" value="'.$field['radiovalues'][$n].'"');
							if ($thisFormSubmitted){
								if (isset($_POST[$field['id']])&&$_POST[$field['id']]==$field['radiovalues'][$n]){
									echo(' checked="checked"');
								}
							} else if(isset($field['defaultvalue'])&&$field['radiovalues'][$n]==$field['defaultvalue']) {
								echo(' checked="checked"');
							}
							echo(' />');
							echo($field['radiolabels'][$n]."\n");
							echo("\t\t\t".'</label>'."\n");
						}
						break;
					// Checkboxes
					case "checkbox":
						for ($n=0; $n<count($field['checkboxlabels']);$n++) {
							echo("\t\t\t".'<label>'."\n");
							echo("\t\t\t\t".'<input name="'.$field['id'].'_arr[]" class="fbheckbox" type="checkbox" value="'.$field['checkboxvalues'][$n].'"');
							if ($thisFormSubmitted){
								if (isset($_POST[$field['id'].'_arr'])){
									foreach ($_POST[$field['id'].'_arr'] as $value){
										if ($value==$field['checkboxvalues'][$n]){
											echo(' checked="checked"');
										}	
									}	
								}
							} else if(isset($field['checkboxchecked'])&&$field['checkboxchecked'][$n]==true) {
								// if checkbox is supposed to be checked by default
								echo(' checked="checked"');
							}
							echo(' />');
							echo($field['checkboxlabels'][$n]."\n");
							echo("\t\t\t".'</label>'."\n");
						}
						break;
				}
				// Render Instructions
				if (isset($field['instructions'])){
					echo("\n\t\t\t".'<p class="instructions">'.$field['instructions'].'</p>');
				}
				// Divider
				if ($this->showDividers==true){
					if ($loopCount+1<count($this->fields)){
						if ($this->fields[$loopCount+1]['type']=="fieldset"||$this->fields[$loopCount+1]['type']=="closefieldset"){
							echo(' fblast');
						}
					}
				}
			}
			$loopCount++;
		}

		// All hidden variables
		foreach ($this->fields as $field) {
			if ($field['type']=="hidden"){
				echo('<input name="'.$field['id'].'" type="hidden" value="'.$field['value'].'" />'."\n");
			}
		}
		// Form name hidden variable
		// (in case there is more than one form, we need to be able to tell them apart)
		echo('<input name="fbformname" type="hidden" value="'.$this->formname.'" />'."\n");
		// Submit Button
		echo('<input type="submit" name="submit" value="'.$this->btnlabel.'" class="submitbtn" />'."\n");
		echo('</fieldset>'."\n");
		// close form
		echo('</form>'."\n");		
	}	
}

?>