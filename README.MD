SubwayPHP - php router

## Usage

#### Basic example

    use Subway\Map;
    
    $map = new Map(); // create router
    $map->route('/', function ($request, $route) {
        // home page
    });
    $map->group('/news', function ($group) {
        $group->route('/', function ($request, $route) {
            // news page
        });
        $group->route('/{id:i}', function ($request, $route) {
            $postId = (int)$request->segment(2);
            // article page
        });
    });
    $map->build(); // routes built and ready to use
    $map->dispatch($_SERVER['REQUEST_URI']); // route

#### Install

    composer require alclab/subway-router

## Routes

#### Create routes map

    use Subway\Map;

    $map = new Map();

#### Static path

    $map->route('/foo/bar', function($request, $route) {});

#### Variables in url

    $map->route('/foo/{bar}') // {bar} - any string
    $map->route('/foo/{bar:i}') // {bar:i} - integers only
    $map->route('/foo/{bar:a}') // {bar:a} - allowed alpha (a-zA-Z), underscores and dashes
    $map->route('/foo/{bar:[a-z]{2}\d+}') - pattern matching (case insensitive)

#### Optional segments

    $map->route('/foo?/{bar}') // first segment is optional

#### Groups

    $map->group('/foo', function ($group) {
        $group->route('/', function ($req) {}); // GET /foo
        $group->route('/bar', function ($req) {}); // GET /foo/bar
    });

## Preparing and dispatching routes

#### Build

Your router has to be compiled before dispatching.

    $map->build(); // now router is ready to use

You should re-build your router with any changes in your routes setup

#### Dispatch

    $map->dispatch($_SERVER['REQUEST_URI']);

## Routes estimation and priorities
Static segments has the highest priority.\
Pattern segments has the medium priority.\
Variable (any) segments has the lowest priority.

    $map->route('/foo/{bar}')->name('any')
    $map->route('/foo/{bar:i}')->name('integer')
    $map->route('/foo/bar')->name('static')
    
    $map->dispatch('/foo/bar') // route named 'static' will be loaded
    $map->dispatch('/foo/foo') // route named 'any' will be loaded

Optional segments has less priority than mandatory ones

    $map->route('/foo?/bar')->name('first')
    $map->route('/foo/bar')->name('second')
    
    $map->dispatch('/foo/bar') // route named 'second' will be loaded
    // Technically both routes are fits such request,
    // but 'first' route will get less estimation due to an optional segment.
    // In this example 'first' route will never be loaded

## Middleware hooks

    use Subway\Map;
    use Subway\Route;
    use Subway\Request;
    use Subway\Middleware;
    
    class CustomMiddleware extends Middleware {

        // execute after route estimated
        public function onEstimated(int $rate, Request $request, Route $route) : int {
            // this hook should return integer
            // return -1 to skip current route
            return $rate;
        }

        // execute before route loaded
        public function onResolving(callable $onLoad, Request $request, Route $route) : callable {
            // this hook can by async or syncronous and should return or promise onLoad callback
            return new Promise(resolve => {
                // do something before load route
                resolve(onLoad);
            });
        }

        // execute after route loaded
        public function onResolved(Request $request, Route $route) : void {
            // this hook don't return anything
        }

    }

    $map = new Map();
    
    $map->route('/foo/bar', function ($request, $route) {
        // load page
    })->middleware(new CustomMiddleware());
    
    $map->group('/', function ($group) {
        $group->route('/foo/bar', function ($request, $route) {
            // load page
        });
    })->middleware(new CustomMiddleware());

    $map->build();
    $map->dispatch($_SERVER['REQUEST_URI']);

#### Multiple middleware

    class Logger extends Middleware {
        
        public function onResolved($request, $route) {
            print_r([ $request->path, $request->query, $route->name ]);
        }
        
    }

    class Auth extends Middleware {
        
        public function onResolving($onLoad, $request) {
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

    new Subway\Request('https://example.com/foo/bar?foo=1&bar=2#foo')
        ->url : string // 'https://example.com/foo/bar?foo=1&bar=2#foo'
        ->origin : string // 'https://example.com'
        ->path : string // 'foo/bar'
        ->query : string // 'foo=1&bar=2'
        ->anchor : string // '#foo'
        ->segments : array // [ 'foo', 'bar' ]
        ->segment(int $segmentNumber) : string // get segment by number (starts from 1). Example: $request->segment(2) => 'bar'
        ->keys : array // [ 'foo' => '1', 'bar' => '2' ]
        ->key(string $keyName) : string // get GET property by name. Example: $request->key('bar') => '2'

#### Route

    Subway\Route
        ->name : string // name of route or empty string if not defined
        ->groups : array // array with names of groups which contain this route 
        ->inGroup(string $name) : bool // check if this route in group with specific name
        ->estimate(Subway\Request $request) : int // estimate route (onResolving() middleware hooks will be executed if exists)
        ->getUrl(array $props) : string // get URL of this route. Keys from 'props' parameter will be used to replace variable segments. Example: $map->route('/foo/{bar}')->getUrl([ 'bar' => 'something' ]) will return '/foo/something'
        ->resolve(Subway\Request $request) // load route (onResolving() and onResolved() middleware hooks will be executed if exists)