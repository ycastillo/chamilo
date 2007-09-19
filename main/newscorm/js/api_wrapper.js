/**
 * Wrapper to the SCORM API provided by Dokeos
 * The complete set of functions and variables are in this file to avoid unnecessary file
 * accesses.
 * Only event triggers and answer data are inserted into the final document.
 * @author	Yannick Warnier  - inspired by the ADLNet documentation on SCORM content-side API
 * @package scorm.js
 */
/**
 * Initialisation of the SCORM API section. 
 * Find the SCO functions (startTimer, computeTime, etc in the second section)
 * Find the Dokeos-proper functions (checkAnswers, etc in the third section)
 */
var _debug = false;
var findAPITries = 0;
var _apiHandle = null; //private variable
var errMsgLocate = "Unable to locate the LMS's API implementation";
/**
 * Gets the API handle right into the local API object and ensure there is only one.
 * Using the singleton pattern to ensure there's only one API object.
 * @return	object The API object as given by the LMS
 */
var API = new function ()
{
	if (_apiHandle == null)
	{
		_apiHandle = getAPI();
	}
	return _apiHandle;
}

/**
 * Finds the API on the LMS side or gives up giving an error message
 * @param	object	The window/frame object in which we are searching for the SCORM API
 * @return	object	The API object recovered from the LMS's implementation of the SCORM API
 */
function findAPI(win)
{
	while((win.API == null) && (win.parent != null) && (win.parent != win))
	{
		findAPITries++;
		if(findAPITries>10)
		{
			alert("Error finding API - too deeply nested");
			return null;
		}
		win = win.parent
	}
	return win.API;
}
/**
 * Gets the API from the current window/frame or from parent objects if not found
 * @return	object	The API object recovered from the LMS's implementation of the SCORM API
 */
function getAPI()
{
	//window is the global/root object of the current window/frame
	var MyAPI = findAPI(window);
	//look through parents if any
	if((MyAPI == null) && (window.opener != null) && (typeof(window.opener) != "undefined"))
	{
		MyAPI = findAPI(window.opener);
	}
	//still not found? error message
	if(MyAPI == null)
	{
		alert("Unable to find SCORM API adapter.\nPlease check your LMS is considering this page as SCORM and providing the right JavaScript interface.")
	}
	return MyAPI;
}
/**
 * Handles error codes (prints the error if it has a description)
 * @return	int	Error code from LMS's API
 */
function ErrorHandler()
{
	if(API == null)
	{
		alert("Unable to locate the LMS's API. Cannot determine LMS error code");
		return;
	}
	var errCode = API.LMSGetLastError().toString();
	if(errCode != _NoError)
	{
		var errDescription = API.LMSGetErrorString(errCode);
		if(_debug == true)
		{
			errDescription += "\n";
			errDescription += api.LMSGetDiagnostic(null);
		}
		alert (errDescription);
	}
	return errCode;
}
/**
 * Calls the LMSInitialize method of the LMS's API object
 * @return string	The string value of the LMS returned value or false if error (should be "true" otherwise)
 */
function doLMSInitialize()
{
	if(API == null)
	{
		alert(errMsgLocate + "\nLMSInitialize failed");
		return false;
	}
	var result = API.LMSInitialize("");
	if(result.toString() != "true")
	{
		var err = ErrorHandler();
	}
	return result.toString();
}
/**
 * Calls the LMSFinish method of the LMS's API object
 * @return	string	The string value of the LMS return value, or false if error (should be "true" otherwise)
 */
function doLMSFinish()
{
	if(API == null)
	{
		alert(errMsgLocate + "\nLMSFinish failed");
		return false;
	}
	else
	{
		var result = API.LMSFinish("");
		if(result.toString() != "true")
		{
			var err = ErrorHandler();
		}
	}
	return result.toString();
}
/**
 * Calls the LMSGetValue method
 * @param	string	The name of the SCORM parameter to get
 * @return	string	The value returned by the LMS
 */
function doLMSGetValue(name)
{
	if (API == null)
	{
		alert(errMsgLocate + "\nLMSGetValue was not successful.");
		return "";
	}
	else
	{
		var value = API.LMSGetValue(name);
		var errCode = API.LMSGetLastError().toString();
		if (errCode != _NoError)
		{
			// an error was encountered so display the error description
			var errDescription = API.LMSGetErrorString(errCode);
			alert("LMSGetValue("+name+") failed. \n"+ errDescription);
			return "";
		}
		else
		{
			return value.toString();
		}
	}
}
/**
 * Calls the LMSSetValue method of the API object
 * @param	string	The name of the SCORM parameter to set
 * @param	string	The value to set the parameter to
 * @return  void
 */
function doLMSSetValue(name, value)
{
   if (API == null)
   {
      alert("Unable to locate the LMS's API Implementation.\nLMSSetValue was not successful.");
      return;
   }
   else
   {
      var result = API.LMSSetValue(name, value);
      if (result.toString() != "true")
      {
         var err = ErrorHandler();
      }
   }
   return;
}
/**
 * Calls the LMSCommit method
 */
function doLMSCommit()
{
	if(API == null)
	{      
		alert(errMsgLocate +"\nLMSCommit was not successful.");
		return "false";
	}
	else
	{
		var result = API.LMSCommit("");
		if (result != "true")
		{
			var err = ErrorHandler();
		}
	}
	return result.toString();
}
/**
 * Calls GetLastError()
 */
function doLMSGetLastError()
{
	if (API == null)
	{
		alert(errMsgLocate + "\nLMSGetLastError was not successful.");      //since we can't get the error code from the LMS, return a general error
		return _GeneralError;
	}
	return API.LMSGetLastError().toString();
}
/**
 * Calls LMSGetErrorString()
 */
function doLMSGetErrorString(errorCode)
{
   if (API == null)
   {
      alert(errMsgLocate + "\nLMSGetErrorString was not successful.");
   }

   return API.LMSGetErrorString(errorCode).toString();
}
/**
 * Calls LMSGetDiagnostic()
 */
function doLMSGetDiagnostic(errorCode)
{
   if (API == null)
   {
      alert(errMsgLocate + "\nLMSGetDiagnostic was not successful.");
   }

   return API.LMSGetDiagnostic(errorCode).toString();
}

/**
 * Second section. The SCO functions are located here (handle time and score messaging to SCORM API)
 * Initialisation
 */
var startTime;
var exitPageStatus;
/**
 * Initialise page values
 */
function loadPage()
{
	var result = doLMSInitialize();
	if(result != false)
	{
		var status = doLMSGetValue("cmi.core.lesson_status");
		if(status == "not attempted")
		{
			doLMSSetValue("cmi.core.lesson_status","incomplete");
		}
		exitPageStatus = false;
		startTimer();
	}
}
/**
 * Starts the local timer
 */
function startTimer()
{
	startTime = new Date().getTime();
}
/**
 * Calculates the total time and sends the result to the LMS
 */
function computeTime()
{
	   if ( startTime != 0 )
	   {
	      var currentDate = new Date().getTime();
	      var elapsedSeconds = ( (currentDate - startTime) / 1000 );
	      var formattedTime = convertTotalSeconds( elapsedSeconds );
	   }
	   else
	   {
	      formattedTime = "00:00:00.0";
	   }

	   doLMSSetValue( "cmi.core.session_time", formattedTime );
}
/**
 * Formats the time in a SCORM time format
 */
function convertTotalSeconds(ts)
{
	var sec = (ts % 60);
	ts -= sec;
	var tmp = (ts % 3600);  //# of seconds in the total # of minutes
	ts -= tmp;              //# of seconds in the total # of hours

    // convert seconds to conform to CMITimespan type (e.g. SS.00)
	sec = Math.round(sec*100)/100;
	var strSec = new String(sec);
	var strWholeSec = strSec;
	var strFractionSec = "";
	
	if (strSec.indexOf(".") != -1)
	{
		strWholeSec =  strSec.substring(0, strSec.indexOf("."));
		strFractionSec = strSec.substring(strSec.indexOf(".")+1, strSec.length);
	}
	if (strWholeSec.length < 2)
	{
		strWholeSec = "0" + strWholeSec;
	}
	strSec = strWholeSec;
	if (strFractionSec.length)
	{
		strSec = strSec+ "." + strFractionSec;
	}
	if ((ts % 3600) != 0 )
		var hour = 0;
	else var hour = (ts / 3600);
	if ( (tmp % 60) != 0 )
		var min = 0;
	else var min = (tmp / 60);
	if ((new String(hour)).length < 2)
		hour = "0"+hour;
	if ((new String(min)).length < 2)
		min = "0"+min;
	var rtnVal = hour+":"+min+":"+strSec;
	return rtnVal
}
/**
 * Handles the use of the back button (saves data and closes SCO)
 */
function doBack()
{  
	doLMSSetValue( "cmi.core.exit", "suspend" );
	computeTime();
	exitPageStatus = true;
	var result;
	result = doLMSCommit();
	result = doLMSFinish();
}
/**
 * Handles the closure of the current SCO before an interruption. This is only useful if the LMS
 * deals with the cmi.core.exit, cmi.core.lesson_status and cmi.core.lesson_mode *and* the SCO
 * sends some kind of value for cmi.core.exit, which is not the case here (yet).
 */
function doContinue(status)
{
	// Reinitialize Exit to blank
	doLMSSetValue( "cmi.core.exit", "" );
	var mode = doLMSGetValue( "cmi.core.lesson_mode" );
	if ( mode != "review"  &&  mode != "browse" )
	{
		doLMSSetValue( "cmi.core.lesson_status", status );
	}
	computeTime();
	exitPageStatus = true;
	var result;
	result = doLMSCommit();
	result = doLMSFinish();
}
/**
 * handles the recording of everything on a normal shutdown
 */
function doQuit(status)
{
	computeTime();
	exitPageStatus = true;
	var result;
	result = doLMSCommit();
	result = doLMSSetValue("cmi.core.lesson_status", status);
	result = doLMSFinish();
}
/**
 * Called upon unload event from body element
 */
function unloadPage(status)
{
    if (exitPageStatus != true)
    {
            doQuit( status );
    }
}