<?php
/**
 * parameters are URI sections after the controller/action part using MVC pattern.
 * this is not the querystring.
 * To get querystring parameters use filter_input() or filter_input_array()
 *
 * @return array
 */
function uri_parameters()
{
    # fetch uri
    $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
    # if querystring is present remove it from uri
    $__p = mb_strpos($uri, '?');
    if ($__p > 0) {
        $uri = mb_substr($uri, 0, $__p);
    }
    # grab uri sections
    $params = explode("/", $uri);
    # remove first section, controller and action
    unset($params[0], $params[1], $params[2]);
    # return parameters
    return array_values($params);
}

/**
 * handle the incoming request
 *
 * @return array
 */
function dispatch()
{
    try {
        # grab server data
        $__server = filter_input_array(
            INPUT_SERVER, [
                'REQUEST_URI' => FILTER_DEFAULT,
                'REQUEST_METHOD' => FILTER_DEFAULT
            ]
        );

        # the request
        $request = explode('/', $__server['REQUEST_URI']);

        # http method
        $method = strtolower($__server['REQUEST_METHOD']);

        # controller
        $controller = '\Controllers\\' . ucfirst(strtolower($request[1])) . 'Controller';

        # endpoint, if not set, try {resource}::getIndex()
        if (isset($request[2])) {
            # remove params from action
            $cleaned_request = strpos($request[2], '?') > 0
                ? substr($request[2], 0, strpos($request[2], '?'))
                : $request[2];

            $endpoint = $method . ucfirst(strtolower($cleaned_request));
        } else {
            $endpoint = $method . 'Index';
        }

        # check if the controller exists. if not, throw an exception
        if (!class_exists($controller)) {
            throw new \Exception('Controller class not found.');
        }
        # controller
        $__controller = new $controller();

        # check if the action exists in the controller. if not, throw an exception.
        if (method_exists($__controller, $endpoint) === false) {
            throw new \BadMethodCallException(
                sprintf('Method "%s::%s" does not exist.', get_class($__controller), $method)
            );
        }

        # call function and store results
        $result = call_user_func_array([$__controller, $endpoint], uri_parameters());

    } catch (\Exception $e) {
        # catch any exceptions and report the problem
        $result = ['error' => $e];
    }

    # return result
    return $result;
}

# end
