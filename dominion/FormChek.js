var reWhitespace = /^\s+$/
var reLetter = /^[a-zA-Z]$/
var reAlphabetic = /^[a-zA-Z]+$/
var reAlphanumeric = /^[a-zA-Z0-9]+$/
var reDigit = /^\d/
var reLetterOrDigit = /^([a-zA-Z]|\d)$/
var reInteger = /^\d+$/
var reEmail = /^.+\@.+\..+$/
var digits = "0123456789";
var lowercaseLetters = "abcdefghijklmnopqrstuvwxyz"
var uppercaseLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
var whitespace = " \t\n\r";
var mPrefix = "You did not enter a value into the "
var mSuffix = " field. This is a required field. Please enter it now."
var sPassword = "Password"
var sPassword2 = "Repeat Password"
var sDomName = "Dominion Name"
var sUSLastName = "Last Name"
var sUSFirstName = "First Name"
var sUserName = "Username"
var sDateOfBirth = "Date of Birth"
var sEmail = "Email"
var iName = "This field must contain only names. The field does not accept names that contain numbers."
var iEmail = "This field must be a valid email address (like foo@bar.com). Please reenter it now."
var pEntryPrompt = "Please enter a "
var pEmail = "valid email address (like foo@bar.com)."
var defaultEmptyOK = false

function isEmpty(s)
{   return ((s == null) || (s.length == 0))
}

function isWhitespace (s)

{   
    return (isEmpty(s) || reWhitespace.test(s));
}

function charInString (c, s)
{   for (i = 0; i < s.length; i++)
    {   if (s.charAt(i) == c) return true;
    }
    return false
}

function isLetter (c)
{   return reLetter.test(c)
}



function isDigit (c)
{   return reDigit.test(c)
}

function isLetterOrDigit (c)
{   return reLetterOrDigit.test(c)
}


function isInteger (s)

{   var i;

    if (isEmpty(s)) 
       if (isInteger.arguments.length == 1) return defaultEmptyOK;
       else return (isInteger.arguments[1] == true);

    return reInteger.test(s)
}

function isAlphabetic (s)

{   var i;

    if (isEmpty(s)) 
       if (isAlphabetic.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphabetic.arguments[1] == true);

    else {
       return reAlphabetic.test(s)
    }
}

function isAlphanumeric (s)

{   var i;

    if (isEmpty(s)) 
       if (isAlphanumeric.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphanumeric.arguments[1] == true);

    else {
       return reAlphanumeric.test(s)
    }
}

function isEmail (s)

{   if (isEmpty(s)) 
       if (isEmail.arguments.length == 1) return defaultEmptyOK;
       else return (isEmail.arguments[1] == true);
    
    else {
       return reEmail.test(s)
    }
}

function prompt (s)
{   window.status = s
}

function promptEntry (s)
{   window.status = pEntryPrompt + s
}

function warnEmpty (theField, s)
{   theField.focus()
    alert(mPrefix + s + mSuffix)
    return false
}

function warnInvalid (theField, s)
{   theField.focus()
    theField.select()
    alert(s)
    return false
}

function checkName (theField, s, emptyOK)
{
    if (checkName.arguments.length == 2) emptyOK = defaultEmptyOK;
    if ((emptyOK == true) && (isEmpty(theField.value))) return true;

    if (isWhitespace(theField.value)) 
       return warnEmpty (theField, s);

    if(isAlphabetic(theField.value))
	{
		return true;
	}

    return warnInvalid(theField, iName);
}

function checkString (theField, s, emptyOK)
{   
    if (checkString.arguments.length == 2) emptyOK = defaultEmptyOK;
    if ((emptyOK == true) && (isEmpty(theField.value))) return true;
    if (isWhitespace(theField.value)) 
       return warnEmpty (theField, s);
    else return true;
}

function checkEmail (theField, emptyOK)
{   if (checkEmail.arguments.length == 1) emptyOK = defaultEmptyOK;
    if ((emptyOK == true) && (isEmpty(theField.value))) return true;
    else if (!isEmail(theField.value, false)) 
       return warnInvalid (theField, iEmail);
    else return true;
}