<?
/*	
 *	Author:			Erick Vavretchek -> erick at vavre dot net	***
 *	Country:		Brazil										***
 *	Girlfriend:		Ana Luiza :D								***
 *	First release:	15/06/2004									***
 *
 *
 *	validaForms
 *	===========
 *
 *	validaForms is very simple to use.
 *	This Class is meant to validate html forms.
 *	It has support for multiple languages and also has some intersting features
 *	like associate a textbox with a selectbox. It's useful when you must
 *	receive a input text filled when the user select "others" for instance in a
 *	<select> filed. The same occurs for the radio fields when the user click on
 *	"yes" and must specify what in a related textbox.
 *
 *	Validation Features:
 *		- Multi-language support (currently: english and portuguese);
 *		- Numeric, alphanumeric, lettersonly, required, min and max length;
 *		- Date;
 *		- E-mail;
 *		- Passwords;
 *		- Checkbox;
 *		- Dropdown and related field (<select>);
 *		- Radiobutton and related field;
 *
 *	For the multi-language features we have one file for each language we want 
 *	to support. These are PHP files wich contains the error messages and are 
 *	stored in a folder called "langs" in the same level of this class.
 *	The default language if not specified is english.
 *
 *	To set the apropriated language you should do:
 *		$vFs = new validaForms;			// instantiate the class
 *		$vFs->language = "portugues";	// and then set the language var.
 *	The string passed for the language var MUST be the name of the file in the
 *	"langs" folder.
 *	In this first release the Class supports only two languages: english and portuguese.
 * 
 *	The language files:
 *						./langs/english.lang.php
 *						./langs/portugues.lang.php
 *	Main Methods:
 *						getErrorMsgs()
 *						textBox()
 *						selectBox()
 *						radioButton()
 *						passwordCheck()
 *						dateCheck()
 *						checkBox()
 *						emailCheck()
 *
 *	All of them are fully documented, except the secondary Methods below:
 *
 *	Secondary Methods (You'll never access them diretcly):
						catchEmail()
						catchDate()
						typeCheck()
						catchLetters()
						catchNumbers()
						catchLettersAndNumbers()

 */

class validaForms
{
	var $campo;			// Nome do campo
	var $valor;			// Valor do campo
	var $valor2;		// Valor do campo de confirmacao de senha
	var $type;			// Tipo do campo alpha=1 numeric=2 alphanumeric=3
	var $tamanhoMin;	// Tamanho mínimo do campo
	var $tamanhoMax;	// Tamanho máximo do campo
	var $requerido;		// Campo obrigatório ou nao -- obrigatório=1 ou 0 para não obrigatório
	var $notValor;		// Valor que nao pode estar selecionado no selectbox
	var $erro;			// Mensagem de erro que será retornada para o usuário
	var $relacao;		// Id do campo que tem relacionamento com outro campo. Ex: Outros
	var $campoRel;		// Nome do campo do relacionado com $relacao
	var $valorRel;		// Valor do campo do relacionado com $relacao
	var $language;		// Language file
	
/*	
 *	getErrorMsgs
 *	============
 *
 *	This Method is used to return the error message in the specified language.
 *	Default language is english. Portuguese supported. Add another language if you want, let me know ;)
 *	This Method will be called when an error occurs.
 *	Most of the params below are set during the script, maybe you'll never use it directly.
 *
 *	Params:	
 *			$error		=> It's the name of the error related in the language file;
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML checkbox;
 *			$tamanhoMin	=> This param tells de min length for the input, deafult is 0;
 *			$tamanhoMax	=> This param tells de max length for the input, default is null;
 */

	function getErrorMsgs($error,$campo,$tamanhoMin,$tamanhoMax)
	{
		// error messages hardcoded by JN instead of included from external file
		$ERROR_MSG["textBox_Req"]			= "The field \"$campo\" is required";
		$ERROR_MSG["textBox_Len"]			= "The field \"$campo\" should be ($tamanhoMin-$tamanhoMax) long";
		$ERROR_MSG["textBox_LenMin"]		= "The field \"$campo\" should be at least $tamanhoMin characters";
		$ERROR_MSG["textBox_LenMax"]		= "The field \"$campo\" can't exceed $tamanhoMax characters";
		$ERROR_MSG["selectBox_Not"]			= "Select a valid option for the field \"$campo\"";
		$ERROR_MSG["check_radioButton"]		= "Select an option for \"$campo\"";
		$ERROR_MSG["passwordCheck_Match"]	= "Passwords don't match, type them again";
		$ERROR_MSG["dateCheck_Format"]		= "Type a date in the formart (dd/mm/yyyy) for the field \"$campo\"";
		$ERROR_MSG["emailCheck"]			= "Type a valid e-mail address for the field \"$campo\"";
		$ERROR_MSG["lettersCheck"]			= "Type only letters for the field \"$campo\".";
		$ERROR_MSG["numbersCheck"]			= "Type only numbers for the field \"$campo\".";
		$ERROR_MSG["numbers_lettersCheck"]  = "Type only numbers or letters for the field \"$campo\".";
		//if (!isset($this->language))
			//$this->language = "english";
		//require_once "langs/".$this->language.".lang.php";
		return $ERROR_MSG[$error];
	}



/*	
 *	textBox
 *	=======
 *
 *	This Method is used to validate html forms type="text".
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML input
						   eg. <input name="test"> You should use $teste;
 *			$requerido	=> This param tells if your input is required or not:
 *								$requerido=1 (default)
 *								$requerido=0 for not required;
 *			$type		=> This param tells if your input must be alpha, numeric or alphanumeric:
 *								$type = null (default)	
 *								$type = 1 for alpha
 *								$type = 2 for numeric
 *								$type = 3 for alphanumeric;
 *			$tamanhoMin	=> This param tells de min length for the input, deafult is 0;
 *			$tamanhoMax	=> This param tells de max length for the input, default is null;
 */

	function textBox($campo, $valor, $requerido=1, $type=null, $tamanhoMin=0, $tamanhoMax=null)
	{
		if ($requerido)
		{
			if (!($valor))
			{
				$this->erro = $this->getErrorMsgs("textBox_Req",$campo,null,null);
				return false;
			}
		}
		
		if ($tamanhoMax==null)
		{
			if (strlen($valor)<$tamanhoMin)
			{
				$this->erro = $this->getErrorMsgs("textBox_LenMin",$campo,$tamanhoMin,$tamanhoMax);
				return false;
			}
		}
		elseif (strlen($valor)<$tamanhoMin)
		{
			$this->erro = $this->getErrorMsgs("textBox_LenMin",$campo,$tamanhoMin,$tamanhoMax);
			return false;
		}
		elseif (strlen($valor)>$tamanhoMax)
		{
			$this->erro = $this->getErrorMsgs("textBox_LenMax",$campo,$tamanhoMin,$tamanhoMax);
			return false;
		}

		if ($type!==null)
			return $this->typeCheck($campo,$valor,$type);

		return true;
	}



/*	
 *	selectBox
 *	=========
 *
 *	This Method is used to validate html forms <select>.
 *  It also contains a feature to validate the possibility of the "others" selection,
 *	in this case you should specify what input is related with the <select>. See below.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML <select>
 *						   eg. for <select name="test"> You should use $test;
 *			$notValor	=> This param specify wich value your <select> must not have, default is null;
 *			$relacao	=> This param specify wich is the value that must implicity the fillment of another;
 *						   eg: If "other" is selected, the <input type=text name="others"> is required;
 *			$campoRel	=> It's the name that will appear in the error message of the related input in the situation above;
 *			$valorRel	=> It's the name of the HTML input of the related field above;
 *			$tamanhoMin	=> This param tells de min length for the input of the related field above, deafult is 0;
 *			$tamanhoMax	=> This param tells de max length for the input of the related field above, default is null;
 *			$type		=> This param tells if your input must be alpha, numeric or alphanumeric:
 *								$type = null (default)	
 *								$type = 1 for alpha
 *								$type = 2 for numeric
 *								$type = 3 for alphanumeric;
 */
	
	function selectBox($campo, $valor, $notValor=null, $relacao=null, $campoRel=null, $valorRel=null, $tamanhoMin=null, $tamanhoMax=null, $type=null)
	{
		if ($valor==$notValor)
		{
			$this->erro = $this->getErrorMsgs("selectBox_Not",$campo,null,null);
			return false;
		}
		if ($relacao)
		{
			if ($valor==$relacao)
				return $this->textBox($campoRel,$valorRel,1,$type,$tamanhoMin,$tamanhoMax);
		}
		return true;
	}



/*	
 *	radioButton
 *	===========
 *
 *	This Method is used to validate html forms type=radio.
 *  It also contains a feature to relate a input type=text with one of the selected radios.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML input:
 *						   eg: for <input type="radio" name="test"> You should use $test;
 *			$relacao	=> This param specify wich is the value that must implicity the fillment of another;
 *						   eg: If "yes" is selected, the <input type=text name="specify"> is required;
 *			$campoRel	=> It's the name that will appear in the error message of the related input in the situation above;
 *			$valorRel	=> It's the name of the HTML input of the related field above;
 *			$tamanhoMin	=> This param tells de min length for the input of the related field above, deafult is 0;
 *			$tamanhoMax	=> This param tells de max length for the input of the related field above, default is null;
 *			$type		=> This param tells if your input must be alpha, numeric or alphanumeric:
 *								$type = null (default)	
 *								$type = 1 for alpha
 *								$type = 2 for numeric
 *								$type = 3 for alphanumeric;
 */
	
	function radioButton($campo, $valor, $relacao=null, $campoRel=null, $valorRel=null, $tamanhoMin=null, $tamanhoMax=null, $type=null)
	{
		if ($valor==null)
		{
			$this->erro = $this->getErrorMsgs("check_radioButton",$campo,null,null);
			return false;
		}
		else
		{
			if ($relacao)
			{
				if ($valor==$relacao)
					return $this->textBox($campoRel,$valorRel,1,$type,$tamanhoMin,$tamanhoMax);
			}	
		}
		return true;
	}



/*	
 *	passwordCheck
 *	=============
 *
 *	This Method is used to validate typed and re-typed passwords.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML input type=password 1:
 *						   eg: for <input type="password" name="pass1"> You should use $pass1;
 *			$valor2		=> It's the name of your HTML input type=password 2:
 *						   eg: for <input type="password" name="pass2"> You should use $pass2;
 *			$tamanhoMin	=> This param tells de min length for the input of the related field above, deafult is 0;
 *			$tamanhoMax	=> This param tells de max length for the input of the related field above, default is null;
 *			$type		=> This param tells if your input must be alpha, numeric or alphanumeric:
 *								$type = null (default)	
 *								$type = 1 for alpha
 *								$type = 2 for numeric
 *								$type = 3 for alphanumeric;
 */

	function passwordCheck($campo,$valor,$valor2,$tamanhoMin,$tamanhoMax,$type=null)
	{
		if ($valor!==$valor2)
		{
			$this->erro = $this->getErrorMsgs("passwordCheck_Match",null,null,null);
			return false;
		}
		else
		{
			return $this->textBox($campo,$valor,1,$type,$tamanhoMin,$tamanhoMax);
		}
		return true;

	}



/*	
 *	dateCheck
 *	=========
 *
 *	This Method is used to validate date.
 *  This Method use another one called catchDate wich assumes the format dd/mm/yyyy,
 *	you may change to satisfy your country format.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML input;
 *			$requerido	=> This param tells if your input is required or not:
 *								$requerido=1 (default)
 *								$requerido=0 for not required;
 */
	
	function dateCheck($campo,$valor,$requerido=1)
	{
		if ($requerido)
		{
			if (!$valor)
			{
				return $this->textBox($campo,$valor,1,null,0,1000);
			}
			else
			{
				return $this->catchDate($campo,$valor);
			}
		}
		else
		{
			if (!$valor)
			{
				return true;
			}
			else
			{
				return $this->catchDate($campo,$valor);
			}
		}
	}
	


/*	
 *	checkBox
 *	========
 *
 *	This Method is used to validate html type=checkbox.
 *	At least one of the checkboxes must be checked.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML checkbox;
 */
	
	function checkBox($campo,$valor)
	{
		if (!$valor[0])
		{
			$this->erro = $this->getErrorMsgs("check_radioButton",$campo,null,null);
			return false;
		}
		else
			return true;
	}



/*	
 *	emailCheck
 *	==========
 *
 *	This Method is used to validate html an e-mail type in a type=text.
 *
 *	Params:	
 *			$campo		=> It's the name that will appear in the error message;
 *			$valor		=> It's the name of your HTML checkbox;
 *			$requerido	=> This param tells if your input is required or not:
 *								$requerido=1 (default)
 *								$requerido=0 for not required;
 */

	function emailCheck($campo, $valor, $requerido)
    {
		if ($requerido)
		{
			if (!$valor)
			{
				return $this->textBox($campo,$valor);
			}
			else
			{
				return $this->catchEmail($campo,$valor);
			}
		}
		else
		{
			if (!$valor)
			{
				return true;
			}
			else
			{
				return $this->catchEmail($campo,$valor);
			}
		}
	}
	
/*
 *	The Method below are used during the script. (Secondary Methods)
 *	Probably you'll never access them directly.
 */
	
	function catchEmail($campo,$valor)
	{
		if(!eregi("^([a-z0-9\\_\\.\\-]+)@([a-z0-9\\_\\.\\-]+)\\.([a-z]{2,4})$",$valor)) 
		{
			$this->erro = $this->getErrorMsgs("emailCheck",$campo,null,null);
			return false;
		}
		return true;
	}
	
/*
 * catchDate
 * =========
 *
 * You can change this Method to best fit your demands.
 * Here I use the date format dd/mm/yyyy.
 * You should change the first line to:
 *			list($Month, $Day, $Year) = explode("-",$valor);
 * to attend the format mm-dd-yyyy.
 */
	
	function catchDate($campo,$valor)
	{
		list($Day, $Month, $Year) = explode("/",$valor);
		if (strlen($Year)==4)
		{
			if (!checkdate($Month,$Day,$Year))
			{
				$this->erro = $this->getErrorMsgs("dateCheck_Format",$campo,null,null);
				return false;
			}
		}
		else
		{
			$this->erro = $this->getErrorMsgs("dateCheck_Format",$campo,null,null);
			return false;
		}
		return true;
	}

	function typeCheck($campo,$valor,$type)
	{
		switch ($type) 
		{
			case 1:
			   return $this->catchLetters($campo, $valor);
			   break;
			case 2:
			   return $this->catchNumbers($campo, $valor);
			   break;
			case 3:
			   return $this->catchLettersAndNumbers($campo, $valor);
			   break;
		}
	}
	
	function catchLetters($campo, $valor)
	{
		if (!preg_match('/^[a-zA-ZáàãâÁÀÃÂéÉíÍóõôÓÕÔúüÚÜ[:space:]]+$/', $valor))
		{
			$this->erro = $this->getErrorMsgs("lettersCheck",$campo,null,null);
			return false;
		}
		return true;
	}
	
	function catchNumbers($campo, $valor)
	{
		if (!is_numeric($valor))
		{
			$this->erro = $this->getErrorMsgs("numbersCheck",$campo,null,null);
			return false;
		}
		return true;
	}

	function catchLettersAndNumbers($campo, $valor)
	{
		if (!preg_match('/^[a-zA-Z0-9áàãâÁÀÃÂéÉíÍóõôÓÕÔúüÚÜ[:space:]]+$/', $valor))
		{
			$this->erro = $this->getErrorMsgs("numbers_lettersCheck",$campo,null,null);
			return false;
		}
		return true;
	}
}
?>