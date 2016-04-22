<?php
/**
 * @version    $Id: hqauthenticator.php $
 * @package    Joomla.Tutorials
 * @subpackage Plugins
 * @license    GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

function scoutnetAuth($url, $username, $password, $usernameParameterName, $passwordParameterName, $cookieFile)
{
    $loginFields = $usernameParameterName."=".$username."&".$passwordParameterName."=".$password;
    // error_log($loginFields, 0);   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginFields);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //cmj fix för scoutnet ssl
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //cmj fix2 för scoutnet ssl på dev-miljön
    $buffer = curl_exec($ch);
    // print curl_error($ch); //DEBUG
    curl_close($ch);
    
    if (strpos($buffer, $username."</span>") !== false) {
    	error_log("HQ Login TRUE", 0);
 	return true; //inloggad;
    } else {
    	error_log("HQ Login FALSE", 0);
     	// error_log($buffer, 0);   	
	return false; //inte inloggad
    }
}

/**
 * Example Authentication Plugin.  Based on the example.php plugin in the Joomla! Core installation
 *
 * @package    Joomla.Tutorials
 * @subpackage Plugins
 * @license    GNU/GPL
 */
class plgAuthenticationhqscoutnetauth extends JPlugin
{
    /**
     * This method should handle any authentication and report back to the subject
     * This example uses simple authentication - it checks if the password is the reverse
     * of the username (and the user exists in the database).
     *
     * @access    public
     * @param     array     $credentials    Array holding the user credentials ('username' and 'password')
     * @param     array     $options        Array of extra options
     * @param     object    $response       Authentication response object
     * @return    boolean
     * @since 1.5
     */

    function onUserAuthenticate( $credentials, $options, &$response )
    {
        /*
         * Here you would do whatever you need for an authentication routine with the credentials
         *
         * In this example the mixed variable $return would be set to false
         * if the authentication routine fails or an integer userid of the authenticated
         * user if the routine passes
         */
        $db = JFactory::getDbo();
	$query	= $db->getQuery(true)
		->select('id')
		->from('#__users')
		->where('username=' . $db->quote($credentials['username']));

	$db->setQuery($query);
	$result = $db->loadResult();

	if (!$result) {
	    $response->status = 'STATUS_FAILURE';
	    $response->error_message = 'HQ: Username does not exist';
	}

	/**
	 * To authenticate, the username must exist in the database, and the password should be equal
	 * to the reverse of the username (so user joeblow would have password wolbeoj)
	 */
	// if($result && ($credentials['username'] == strrev( $credentials['password'] )))
  // if($result && ($credentials['password'] == '1234'))

  if ($result && scoutnetAuth($this->params->get('loginUrl'), $credentials['username'], $credentials['password'], $this->params->get('usernameParameterName'), $this->params->get('passwordParameterName'), $this->params->get('cookieFile')))
	{
	    $email = JUser::getInstance($result); // Bring this in line with the rest of the system
	    $response->email = $email->email;
	    $response->status = JAuthentication::STATUS_SUCCESS;
	}
	else
	{
	    $response->status = JAuthentication::STATUS_FAILURE;
	    $response->error_message = 'HQ: Invalid username and password';
	}
    }
}
?>
