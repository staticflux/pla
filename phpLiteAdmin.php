<?php
namespace phpLiteAdmin;
session_start();

/**
 * @global string VERSION
 */
const VERSION = '2.0.0';

/**
 * @global array $Users
 * 
 * An array of users that should be able to login to the site.
 */
$Users = [
    'username' => '$2y$10$KD8r2MpegIYfeZJ1.gFlC.b0h4vPv3am8f2f3PXvpGcW5wmg0RZQ6',
];

/**
 * @global array $Database
 * 
 * Contains the name of the database as the $key and it's location on the file system as the value.
 */
$Database = [
    'MimoCAD' => './website.db',
    'MimoSDR' => './MimoSDR.db',
    'SBUHEMS' => './sbuhems.db',
    'Suffolk' => './suffolk.db',
    'WLVAC' => './wlvac.db',
    'MVAC' => './mvac.db',
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
        global $Users;

        // Reset sessions if there is an empty Users array.
        if (isset($_GET['signout']) OR empty($Users) AND isset($_SESSION))
        {
            unset($_SESSION);
        }

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

/**
 * Database
 */
class Database extends \PDO
{
    /**
     * Database connection.
     */
    public \PDO $db;

    /**
     * File size of the database in bytes.
     */
    public ?int $size = null;

    /**
     *
     */
    public ?array $schema = [];

    /**
     * Setups the Database class so all properties are set correctly and ready for use.
     *
     * @param string $filePath - Path to the database file.
     * @param string $name - Friendly name of the database.
     */
    public function __construct(
        public string $filePath,
        public ?string $name = NULL,
    )
    {
        parent::__construct('sqlite:' . $filePath);
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->query('PRAGMA foreign_keys = ON;');
        $this->getSchema();
    }

    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param string $statement
     * This must be a valid SQL statement template for the target database server.
     * @param array $driver_options
     * This array holds one or more `key => value` pairs to set attribute values for the PDOStatement object that this method returns. You would most commonly use this to set the `PDO::ATTR_CURSOR` value to `PDO::CURSOR_SCROLL` to request a scrollable cursor. Some drivers have driver-specific options that may be set at prepare-time.
     */
    public function prepare(string $statement, array $driver_options = []): \PDOStatement
    {
        return parent::prepare($statement, $driver_options);
    }

    /**
     * https://sqlite.org/schematab.html
     */
    public function getSchema()
    {
        foreach ($this->query('SELECT rowid, * FROM sqlite_schema ORDER BY name;') as $row)
        {
            $this->schema[$row['rowid']] = $row;
        }
    }

    /**
     * Gets the file size of the database on disk.
     * 
     * @return Returns the file size.
     */
    public function getSize(): string
    {
         $B = 1;
        $KB =  $B * 1024;
        $MB = $KB * 1024;
        $GB = $MB * 1024;

        $bytes = filesize($this->filePath);

        $units = match (true)
        {
            $bytes >= $GB => ['suffix' => 'GiB', 'base' => $GB, 'css' => 'text-dark'],
            $bytes >= $MB => ['suffix' => 'MiB', 'base' => $MB, 'css' => 'text-light'],
            $bytes >= $KB => ['suffix' => 'KiB', 'base' => $KB, 'css' => 'text-muted'],
            default       => ['suffix' =>   'B', 'base' =>  $B, 'css' => 'text-danger']
        };

        return sprintf('%.3f', $bytes / $units['base']) . $units['suffix'];
    }

    /**
     * Gets the date this database was last modified
     *
     * @return DateTime with time set to the modifed date of the file.
     */
    public function getModifed(): \DateTime
    {
        return new \DateTime('@' . filemtime($this->filePath));
    }

    /**
     * Gets the version of the database from the database file.
     *
     * @return string SQLite Version.
     */
    public function getVersion(): string
    {
        $select = $this->prepare('SELECT sqlite_version();');
        $select->execute();
        return $select->fetchColumn();
    }
}

foreach ($Database as $name => $path)
{
    static $dbs = [];

    $dbs[] = new Database($path, $name); 
}

$page = new Page();
$page->contain = false;
$page->emit();
?>
        <style>
            /**
             * Dark Mode
             */
            html, body {
                background: #FFF;
                color: #000;
            }
            header {
                background: #000;
            }
            header a {
                color: #FFF;
            }
            aside a {
                color: #000;
            }
            /**
             * Header
             */
            header {
                margin: 0;
                padding: 0;
                line-height: 1em;
            }
            header a {
                padding-top: .75rem;
                padding-bottom: .75rem;
                font-size: 1rem;
                text-decoration: none;
            }
            header .toggler {
                top: .25rem;
                right: 1rem;
            }
            header .form-control {
                padding: .75rem 1rem;
                border-width: 0;
                border-radius: 0;
            }
            /**
             * Sidebar
             */
            aside {
                position: fixed;
                top: 2.5rem;
                bottom: 0;
                left: 0;
                z-index: 100; /* Behind the navbar */
                box-shadow: inset -1rem 0 1rem rgba(0, 0, 0, .1);
                overflow-y: auto
            }
            aside dl {
                margin: 0;
                padding: 0;
            }
            aside dl dt {
                margin: 0;
                padding: 0;
            }
            aside dl dd {
                margin: 0;
                padding: 0;
            }
            /**
             * Content
             */
            main {
                padding-top: 1rem;
            }
        </style>
        <header class="navbar sticky-top flex-md-nowrap p-0 shadow">
            <a class="col-md-3 col-lg-2 me-0 px-3" href="<?=$_SERVER['SCRIPT_NAME']?>">phpLiteAdmin <?=VERSION?></a>
            <a class="col-md-3 col-lg-2 me-0 px-3" href="?signout">Sign out</a>
        </header>
        <div class="container-fluid">
            <aside class="col-md-3 col-lg-2 d-md-block sidebar collapse">
<?php   foreach ($dbs as $idx => $db): ?>
                <dl class="flex-column">
                    <dt><a class="nav-link" aria-current="page" href="?db=<?=$idx?>"><?=$db->name?></a></dt>
<?php           foreach ($db->schema as $table):    ?>
                    <dd><a class="nav-link" aria-current="page" href="?db=<?=$idx?>&table=<?=$table['name']?>">[<?=$table['type']?>] <?=$table['name']?></a></dd>
<?php           endforeach; ?>
                </dl>
<?php   endforeach;  ?>
            </aside>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <label>phpLiteAdmin Version</label> <var><?=VERSION?></var><br />
                <label>PHP Version</label> <var><a href="?phpinfo"><?=phpversion()?></a></var><br />
                <label>SQLite Installed</label> <var><?=\SQLite3::version()['versionString']?></var><br />
                <label>Date Time</label> <var><?=date('Y-m-d H:i:s')?></var><br />
<?php   if (isset($_GET['phpinfo'])):   ?>
                <?php phpinfo(); ?>
<?php   elseif (isset($_GET['db'])):   ?>
                <label>Database name</label> <var><?=$dbs[$_GET['db']]->name?></var><br />
                <label>Path to database</label> <var><?=$dbs[$_GET['db']]->filePath?></var><br />
                <label>Size of database</label> <var><?=$dbs[$_GET['db']]->getSize()?></var><br />
                <label>Database last modified</label> <var><?=$dbs[$_GET['db']]->getModifed()->format('Y-m-d H:i:s')?></var><br />
                <label>SQLite version</label> <var><?=$dbs[$_GET['db']]->getVersion()?></var><br />
<?php   endif;  ?>
            </main>
        </div>
