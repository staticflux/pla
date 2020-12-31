<?php
namespace phpLiteAdmin;
session_start();

/**
 * @global array $Users
 * 
 * An array of users that should be able to login to the site.
 */
$Users = [
];

/**
 * @global array $Database
 * 
 * Contains the name of the database as the $key and it's location on the file system as the value.
 */
$database = [
    'MimoCAD' => './MimoCAD.db',
];

/**
 * Contains all of the logic for building our page.
 */
class Page
{
    /**
     * @property bool $emitted - Has the page header been sent?
     */
    public bool $emitted = false;

    /**
     * @param string $title - HTML Page Title.
     * @param bool $contain - Should the contain class be used to contain the HTML body.
     */
    public function __construct(
        public string $title = 'phpLiteAdmin',
        public bool $contain = true,
    ) {
        // Before we do anything or allow anything, we make sure the client is authenticated with the page.
        if (!Access::granted())
        {
            header('401 Unauthorized');
            $this->title = 'Login';
            $this->contain = false;
            $this->emit();
            echo Access::authenticate();
            $this->__destruct();
            exit();
        }
    }

    /**
     * Emits the page header and start of the body into the stream.
     */
    public function emit()
    {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
                <title>{$this->title}</title>
            </head>
            <body>

        HTML;

        if ($this->contain)
        {
            echo <<<HTML
                    <main class="container">

            HTML;
        }

        $this->emitted = true;
    }

    /**
     * Page footer, only emits when the body has actualy been sent to the client.
     */
    public function __destruct()
    {
        if (!$this->emitted)
            return;

        if ($this->contain)
        {
            echo <<<HTML
                    </main>

            HTML;
        }

        echo <<<HTML
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
            </body>
        </html>
        HTML;
    }
}

/**
 * Access control class.
 * 
 * A users authentication goes though here.
 * It also has the methods for editing the global $Users array.
 */
class Access
{
    const CSS =<<<CSS
            html, body {
                height: 100%;
            }
            body {
                display: flex;
                align-items: center;
                padding-top: 40px;
                padding-bottom: 40px;
                background-color: #f5f5f5;
            }
            .form-signin {
                width: 100%;
                max-width: 330px;
                padding: 15px;
                margin: auto;
            }
            .form-signin .checkbox {
                font-weight: 400;
            }
            .form-signin .form-control {
                position: relative;
                box-sizing: border-box;
                height: auto;
                padding: 10px;
                font-size: 16px;
            }
            .form-signin .form-control:focus {
                z-index: 2;
            }
            .form-signin input[type="email"] {
                margin-bottom: -1px;
                border-bottom-right-radius: 0;
                border-bottom-left-radius: 0;
            }
            .form-signin input[type="password"] {
                margin-bottom: 10px;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
            }
CSS;

    /**
     * Asks the question is Access Granted? And finds the answer.
     * 
     * @return TURE when they are logged int, FALSE otherrwise.
     */
    public static function granted(): bool
    {
        global $Users;

        // Check login form and verify.
        if (!empty($_POST['user']) AND !empty($_POST['pass']) AND isset($Users[$_POST['user']]) AND password_verify($_POST['pass'], $Users[$_POST['user']]))
        {
            if (isset($_POST['remember']))
            {
                setcookie(session_name(), session_id(), strtotime('+1 Month'), '/', $_SERVER['HTTP_HOST'], true, true);
            }

            $_SESSION['loggedIn'] = true;

            return true;
        }

        // Check sessions.
        if (isset($_SESSION) AND isset($_SESSION['loggedIn']) AND $_SESSION['loggedIn'] === true)
        {
            return true;
        }


        return false;
    }

    /**
     * Authenticate a user by presenting them a login prompmt.
     */
    public static function authenticate(): void
    {
        if (self::addUser())
        {
            $CSS = self::CSS;
            echo <<<HTML
                    <style>
            {$CSS}
                    </style>
                    <main class="form-signin text-center">
                        <form method="post">
                            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>
                            <label for="inputUser" class="visually-hidden">Username</label>
                            <input type="text" id="inputUser" class="form-control" placeholder="Username" name="user" required autofocus>
                            <label for="inputPassword" class="visually-hidden">Password</label>
                            <input type="password" id="inputPassword" class="form-control" placeholder="Password" name="pass" required>
                            <div class="checkbox mb-3">
                                <label>
                                    <input type="checkbox" name="remember"> Remember me
                                </label>
                            </div>
                            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
                        </form>
                    </main>

            HTML;
        }
    }

    /**
     * Adds a user based on $_POST form data.
     * This pulls from the global $Users array to make sure that:
     *   1. The user array is empty, thus should be allowed for first time use.
     *   2. A username is set, but that username is not already in use.
     *   3. The passwords match.
     * If all of these conditions are met, it will add the user to the $Users array.
     * @return TRUE on success or FALSE on failure.
     */
    public static function addUser(): bool
    {
        global $Users;

        if (isset($_POST['user']) AND isset($_POST['pass']) AND isset($_POST['passConfirm']))
        {
            if (!empty($Users) AND !Access::granted())
                throw new \Exception('You must be logged in to add a new user.', 1);

            if (isset($Users[$_POST['user']]))
                throw new \Exception('User name already taken.', 1);

            if ($_POST['pass'] !== $_POST['passConfirm'])
                throw new \Exception('Passwords do not match.', 1);

            self::addUserToArray($_POST['user'], $_POST['pass']);
        }

        if (empty($Users) AND empty($_POST))
        {
            $CSS = self::CSS;
            echo <<<HTML
                    <style>
            {$CSS}
                    </style>
                    <main class="form-signin text-center">
                        <form method="post">
                            <h1 class="h3 mb-3 fw-normal">Add New User</h1>
                            <label for="inputUser" class="visually-hidden">Username</label>
                            <input type="text" id="inputUser" class="form-control" placeholder="Username" name="user" required autofocus>
                            <label for="inputPassword1" class="visually-hidden">Password</label>
                            <input type="password" id="inputPassword1" class="form-control" placeholder="Password" name="pass" required>
                            <label for="inputPassword2" class="visually-hidden">Password</label>
                            <input type="password" id="inputPassword2" class="form-control" placeholder="Password" name="passConfirm" required>
                            <div class="checkbox mb-3">
                                <label>
                                    <input type="checkbox" name="remember"> Remember me
                                </label>
                            </div>
                            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
                        </form>
                    </main>

            HTML;
            return false;
        }

        return true;
    }

    /**
     * This is a document mutating function.
     * If this was a rust function, I would mark this as unsafe.
     * 
     * @param string $user - Username
     * @param string $pass - Password
     * @return bool TRUE on Success or FALSE on Failure.
     */
    public static function addUserToArray(string $user, string $pass): bool
    {
        global $Users;

        $document = file_get_contents(__FILE__);
        $userStrStart = strpos($document, '$Users = [' . "\n");
        $userStrEnd = strpos($document, '];', $userStrStart);

        $Users[$user] = password_hash($pass, PASSWORD_DEFAULT);
        $UsersArrayRAW = '';
        foreach ($Users as $user => $hash)
            $UsersArrayRAW = "    '$user' => '$hash'," . "\n";

        $document = substr_replace($document, $UsersArrayRAW, $userStrEnd, 0);

        file_put_contents(__FILE__, $document);

        return true;
    }
}

$page = new Page();
$page->emit();
?>
        <h1>Session</h1>
        <pre><?=print_r($_SESSION, true)?></pre>
        <h2>Cookies</h2>
        <pre><?=print_r($_COOKIE, true)?></pre>
        Hello World
