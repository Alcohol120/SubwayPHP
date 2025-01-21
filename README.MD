SubwayPHP - php router

## Usage

#### Basic example

    use Subway\Map;
    
    $map = new Map(); // create router
    $map->route('GET', '/', function ($request, $response, $route) {
        // home page
        return $response->body('home page');
    });
    $map->group('/news', function ($group) {
        $group->route('GET', '/', function ($request, $response, $route) {
            // news page
            return $response->body('news');
        });
        $group->route('GET', '/{id:i}', function ($request, $response, $route) {
            $postId = (int)$request->segment(2);
            // article page
            if($postId < 1) {
                return $response->status(404)->body('Not found');
            } else return $response->body("Article: {$pageId}");
        });
    });
    $map->build(); // routes built and ready to use
    $map->dispatch(); // route

#### Install

    composer require alclab/subway-router

## Routes

#### Create routes map

    use Subway\Map;

    $map = new Map();

#### Static path

    $map->route('GET', '/foo/bar', function($request, $route) {});

#### Variables in url

    $map->route('GET', '/foo/{bar}') // {bar} - any string
    $map->route('GET', '/foo/{bar:i}') // {bar:i} - integers only
    $map->route('GET', '/foo/{bar:a}') // {bar:a} - allowed alpha (a-zA-Z), underscores and dashes
    $map->route('GET', '/foo/{bar:[a-z]{2}\d+}') - pattern matching (case insensitive)

#### Optional segments

    $map->route('GET', '/foo?/{bar}') // first segment is optional

#### Groups

    $map->group('/foo', function ($group) {
        $group->route('GET', '/', function ($req) {}); // GET /foo
        $group->route('GET', '/bar', function ($req) {}); // GET /foo/bar
    });

## Preparing and dispatching routes

#### Build

Your router has to be compiled before dispatching.

    $map->build(); // now router is ready to use

You should re-build your router with any changes in your routes setup

#### Dispatch

    // using current request data from $_SERVER, $_POST, $_COOKIES etc...
    $map->dispatch();

    // using custom Request instance
    $req = new Request(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI'],
        $_POST,
        getallheaders(),
        $_COOKIE,
        @file_get_contents('php://input'),
        $_FILES,
    );
    $map->displatch($req);

## Routes estimation and priorities
Static segments has the highest priority.\
Pattern segments has the medium priority.\
Variable (any) segments has the lowest priority.

    $map->route('GET', '/foo/{bar}')->name('any')
    $map->route('GET', '/foo/{bar:i}')->name('integer')
    $map->route('GET', '/foo/bar')->name('static')
    
    $map->dispatch(new Request('GET', '/foo/bar')) // route named 'static' will be loaded
    $map->dispatch(new Request('GET', '/foo/foo')) // route named 'any' will be loaded

Optional segments has less priority than mandatory ones

    $map->route('GET', '/foo?/bar')->name('first')
    $map->route('GET', '/foo/bar')->name('second')
    
    $map->dispatch(new Request('GET', '/foo/bar')) // route named 'second' will be loaded
    // Technically both routes are fits such request,
    // but 'first' route will get less estimation due to an optional segment.
    // In this example 'first' route will never be loaded

## Middleware hooks

    use Subway\Map;
    use Subway\Route;
    use Subway\Request;
    use Subway\Response;
    use Subway\Middleware;
    
    class CustomMiddleware extends Middleware {

        // execute after route estimated
        public function onEstimated(int $rate, Request $request, Route $route) : int {
            // this hook should return integer
            // return -1 to skip current route
            return $rate;
        }

        // execute before route loaded
        public function onResolving(callable $onLoad, Request $request, Response $response, Route $route) : callable {
            // this should return function. You can return $onLoad callback to keep default behavior
            return $onLoad;
        }

        // execute after route loaded
        public function onResolved(Request $request, Response $response, Route $route) : void {
            // this hook don't return anything
            // you can still manipulate with $response
            if($response->body !== 'ok') $response->status(404);
        }

    }

    $map = new Map();
    
    $map->route('GET', '/foo/bar', function ($request, $response, $route) {
        return $response->body('not ok');
    })->middleware(new CustomMiddleware());
    
    $map->group('/', function ($group) {
        $group->route('GET', '/foo/bar', function ($request, $response, $route) {
            return $response->body('ok');
        });
    })->middleware(new CustomMiddleware());

    $map->build();
    $map->dispatch();

#### Multiple middleware

    class Logger extends Middleware {
        
        public function onResolved($request, $response, $route) {
            print_r([ $request->path, $request->query, $route->name ]);
        }
        
    }

    class Auth extends Middleware {
        
        public function onResolving($onLoad, $request, $response) {
            // check if user logged in
            if(isset($_SESSION['userId'])) {
                return $onLoad; // return default route loader
            } else {
                return function () {
                    // return 401 code or redirect to login page
                };
            }
        }
        
    }

    $map->group('/', function ($group) {
        ...
    })->middleware(new Logger(), new Auth());

## Object reference

#### Request

    new Subway\Request(string $method, string $url, array $post=[], array $headers=[], array $cookies=[], string | null $body=null, array $files=[])
        ->method : string // returns HTTP method
        ->url : string // returns full request URL
        ->origin : string // returns request origin (https://example.com)
        ->path : string // returns URI path (foo/bar)
        ->query : string // returns query string (foo=1&bar=2)
        ->params : array // returns POST params ([ 'foo' => 1, 'bar' => 2 ])
        ->param(string $paramName) : string // returns POST param by name
        ->segments : array // returns URI segments array ([ 'foo', 'bar' ])
        ->segment(int $segmentNumber) : string // returns segment by number (starts from 1)
        ->keys : array // return GET params ([ 'foo' => '1', 'bar' => '2' ])
        ->key(string $keyName) : string // returns GET property by name
        ->headers : array // returns HTTP headers array
        ->header(string $headerName) : string // returns HTTP headers by name
        ->cookies : array // returns cookies array
        ->cookie(string $cookieName) : string // returns cookie by cookie name
        ->body : string // returns request body string
        ->json(assoc=true) : array // returns array of decoded request body
        ->files : array // returns $_FILES array
        // static methods
        ::getCurrent() : Request // returns Request instance witch represents current request

#### Response

    new Subway\Response()
        ->status : int // returns status code
        ->headers : array // returns headers array
        ->body : ?string // returns body or null
        ->json : ?array // returns content array or null
        ->status(int $statusCode) : $this // set status code
        ->header(string $name, string $value) : $this // set header
        ->body(string $data) : $this // set body content
        ->json(array $data) : $this // set body content as array
        ->redirect(string $url, int $statusCode=302) : void // redirects to given URL with given status code (default 302)

#### Route

    Subway\Route
        ->method : string // http method
        ->name : string // name of route or empty string if not defined
        ->groups : array // array with names of groups which contain this route 
        ->inGroup(string $name) : bool // check if this route in group with specific name
        ->estimate(Subway\Request $request) : int // estimate route (onResolving() middleware hooks will be executed if exists)
        ->getUrl(array $props) : string // get URL of this route. Keys from 'props' parameter will be used to replace variable segments. Example: $map->route('/foo/{bar}')->getUrl([ 'bar' => 'something' ]) will return '/foo/something'
        ->resolve(Subway\Request $request) // load route (onResolving() and onResolved() middleware hooks will be executed if exists)