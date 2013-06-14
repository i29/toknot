<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Admin\AdminBase;
use Toknot\User\CurrentUser;

class Login extends AdminBase {
    protected $permissions = 0777;
    public function GET() {
        $this->FMAI->display('login');
    }
    public function POST() {
        $userName = $_POST['username'];
        $password = $_POST['password'];
        $user = CurrentUser::login($userName, $password);
        if(isset($_POST['week'])) {
            $user->setLoginExpire('1w');
        }
        if($user) {
            $this->setAdminLogin($user);
        }
    }
}

?>