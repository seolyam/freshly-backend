$app->get('/test-db', function (Request $request, Response $response, $args) {
    $users = Capsule::table('products')->get(); // Just to see if it fetches data
    $response->getBody()->write($users);
    return $response;
});
