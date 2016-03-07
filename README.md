# PHP - Simple API

The Simple API class allows you to build a RESTful API in seconds. Form validation is provided in a short-form syntax, which allows you to spend less time on repititive tasks, and more time on the business logic.

## Class Features ##
* Easy request routing
* Simplified parameter / form validation
* Support for Cross-Origin requests
* Request tokens for uniquely identifying incoming requests
* API key support

## Examples ##

First, include the SimpleAPI file.
```php
require('src/SimpleAPI.php');
```

Next, get a `Request` object that can be used to handle the incoming request. Note: the instantiated `$request` object will be used in the examples below.

```php
$request = SimpleAPI\RequestFactory::getRequest();
```

### Routing ###
Conditionally routing a request based on the incoming verb is simple. 

```php
$request->get(function(){
  
  // Handle the incoming GET request

});


$request->post(function(){
  
  // Handle the incoming POST request

});

```
**Enforcing** an HTTP verb is simple as well. Simply omit the callback.

```php
// make sure the incoming request is a POST request
$request->post();
```

In the example above, if the client instead submits a `GET` request to this endpoint (which expects a `POST` request), the following response will *automatically* be sent by the `Request` class.

```json
{
    "code": 405,
    "response_time_seconds": 0,
    "message": "GET not supported"
}
```

### Parameter validation ###
The SimpleAPI also supports a short-form syntax for parameter validation and coercian. The usual syntax for checking whether or not a parameter exists and coercing values looks like this:

```php
// usual method - check for two parameters
if (!isset($_GET['id'])){
  
  throw new Exception('ID is required');

} else if (!is_numeric($_GET['id'])) {

  throw new Exception('ID must be numeric');

} else {
  
  $id = (int) $_GET['id'];

}
```

Using the SimpleAPI, the above validation can be accomplished with the following.

```php
$request->get()->expecting('id|int');
$id = $request->param('id'); // automatically coerced
```

If the `id` parameter is not provided, or is not numeric, the following response will be sent to the client.

```json
{
    "code": 422,
    "response_time_seconds": 0,
    "missing params": [
        "id"
    ]
}
```

As you can see, the SimpleAPI makes building an API easy. You don't have to lookup the HTTP codes for every possible error, the SimpleAPI will automatically respond with the appropriate codes when a fatal condition is met. Futhermore, the output buffer is cleaned before the response, so any notices or errors in your code will never be seen by the client.

Finally, what if you want to validate multiple parameters? The usual syntax (using `isset`) gets even more verbose. But using the SimpleAPI, your code stays simple.

```php
$request->get()->expecting('id|int', 'name|string', 'phone|string');
```

### Optional Values ###
Optional values can be denoted by placing a `?` right after the parameter name.

```php
$request->get()->expecting('id?|int'); // id is now optional
$request->param('id'); // empty value, but no error 
```
### Default values ###

A common use case for any RESTful API is providing default values when a parameter is not specified by the client. Expanding the syntax above, all we need to do to add a default value, is to put the desired default right after the `?`.

```php
$request->get()->expecting('id?1|int'); // id is now optional
$request->param('id'); // if an id wasn't specified, this will be equal to 1
```
