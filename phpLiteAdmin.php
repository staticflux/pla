<?php
namespace phpLiteAdmin;

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

$page = new Page();
$page->title = 'New Title';
$page->emit();

?>
            Hello World
