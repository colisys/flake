// Get route with parameter
Router::get('/user/:id', function (Request $req, Response $res, $id) {
    // All route parameters will injected as function arguments
    $res->json([
        // Get query parameter
        'id'  => $req->get('id', 'unknown'),
        // Get route parameter
        'received_id' => $id,
    ]);
});

// Route with parameter
Router::post('/user/:id', function (Request $req, Response $res, $id) {
    // All route parameters will injected as function arguments
    $body = $req->body();
    $res->json([
        // Get query parameter
        'id'            => $req->get('id', 'unknown'),
        // Get route parameter
        'received_id'   => $id,
        // Get raw body and parse it as JSON
        'received_body' => json_decode($body, true),
        // Get nested data using dot notation
        'message'       => $req->post('data.message', 'Hello World!'),
        // Also works with index
        'list'          => $req->post('data.list.2'),
    ]);
});

// PUT route example
Router::put('/user/:id', function (Request $req, Response $res, $id) {
    $data = $req->json();
    $res->json([
        'method' => 'PUT',
        'id'     => $id,
        'data'   => $data,
    ]);
});

// DELETE route example
Router::delete('/user/:id', function (Request $req, Response $res, $id) {
    $res->json([
        'method' => 'DELETE',
        'id'     => $id,
    ]);
});

// 404 route example (optional)
Router::fallback(function (Request $req, Response $res) {
    $res->status(404)->send('<h1>404 Not Found</h1><p>The route you requested does not exist.</p>');
});
