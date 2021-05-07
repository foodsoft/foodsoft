<?php
/**
 * Foodsoft authentication backend
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Timo
 *
 * (diese datei gehört nach /wiki/inc/auth und wird aktiviert durch
 *   $conf['authtype'] = 'foodsoft';
 *  in /wiki/conf/dokuwiki.php)
 *
 */

define('DOKU_AUTH', dirname(__FILE__));
define('FOODSOFT_PATH', getenv('foodsoftpath') );

require_once(DOKU_AUTH.'/basic.class.php');

// we only accept page ids for auth_plain
if(isset($_REQUEST['u']))
  $_REQUEST['u'] = cleanID($_REQUEST['u']);
if(isset($_REQUEST['acl_user']))
  $_REQUEST['acl_user'] = cleanID($_REQUEST['acl_user']);
// the same goes for password reset requests
if(isset($_POST['login'])){
  $_POST['login'] = cleanID($_POST['login']);
}

class auth_foodsoft extends auth_basic {

    var $users = null;
    var $_pattern = array();

    /**
     * Constructor
     *
     */
    function auth_foodsoft() {
      global $ACT;
      global $from_dokuwiki;

      if( $_REQUEST['do'] == 'login' ) {
        $dir = getcwd();
        chdir( FOODSOFT_PATH );
        $from_dokuwiki = true;
        require_once( FOODSOFT_PATH . '/code/common.php' );
        require_once( FOODSOFT_PATH . '/code/login.php' );
        chdir( $dir );
        $_REQUEST['do'] = 'show';
      }
      if( $_REQUEST['do'] == 'logout' ) {
        unset( $_COOKIE['foodsoftkeks'] );
        setcookie( 'foodsoftkeks', '0', 0, '/', '', TRUE );
        $_REQUEST['do'] = 'show';
      }

      $this->cando['addUser']      = false;
      $this->cando['delUser']      = false;
      $this->cando['modLogin']     = false;
      $this->cando['modPass']      = false;
      $this->cando['modName']      = false;
      $this->cando['modMail']      = false;
      $this->cando['modGroups']    = false;
      $this->cando['getUsers']     = false;
      $this->cando['getUserCount'] = false;
      $this->cando['external']     = true;
      $this->success = true;
      return true;
    }

    /**
     * Check user+password [required auth function]
     *
     * (required yes, but obsolete with cando['external']! (Timo))
     */
    function checkPass($user,$pass){
      return false;
    }

    function trustExternal($user,$pass,$sticky=false){
      global $USERINFO, $angemeldet, $login_gruppen_name;
      global $from_dokuwiki;
      
      if( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {
        $dir = getcwd();
        chdir( FOODSOFT_PATH );
        $from_dokuwiki = true;
        require_once( FOODSOFT_PATH . '/code/common.php' );
        require_once( FOODSOFT_PATH . '/code/login.php' );
        chdir( $dir );
        if( $angemeldet ) {
          $USERINFO['pass'] = 'XXX';
          $USERINFO['name'] = $login_gruppen_name;
          $USERINFO['mail'] = 'n/a';
          $USERINFO['grps'] = array();
          $USERINFO['grps'][0] = 'user';
          $_SERVER['REMOTE_USER'] = $login_gruppen_name;
          $_SESSION[DOKU_COOKIE]['auth']['user'] = $login_gruppen_name;
          $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
          return true;
        }
      }
      return false;
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     */
    function getUserData($user){
      global $login_gruppen_name;
      echo "<h1 class='warn'>getUserData: $user</h1>";
      $info = false;
      if( $angemeldet ) {
        $info['name'] = $login_gruppen_name;
        $info['mail'] = 'n/a';
        $info['grps'] = array();
        $info['grps'][0] = 'user';
      }
      return $info;
    }

}
