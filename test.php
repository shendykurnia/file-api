<?php

$api_url = 'http://localhost:1234/drive/v1';

$name1 = "test-" . microtime(true) . "-" . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT) . ".jpg";
$name2 = "test-" . microtime(true) . "-" . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT) . ".jpg";
$name3 = "test-" . microtime(true) . "-" . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT) . ".jpg";
$name4 = "test-" . microtime(true) . "-" . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT) . ".jpg";

$file1 = __DIR__ . '/sample.jpg';
$file2 = __DIR__ . '/sample2.jpg';

$tmp_folder = '/tmp';

echo "Testing UploadFile API\n";

// upload a file, should be successful
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'POST'],
    ['--form', "file=@$file1"],
    ['--form', "name=$name1"],
    ['', "$api_url/file"]
]);
my_assert(json_decode($output, true) == ['status' => 'ok', 'data' => ['name' => $name1]]);

// upload the same file, should return file_exists error since overwrite != 1
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'POST'],
    ['--form', "file=@$file1"],
    ['--form', "name=$name1"],
    ['', "$api_url/file"]
]);
$output_object = json_decode($output, true);
my_assert($output_object['status'] == 'error' && $output_object['error_code'] == 'file_exists');

// upload a file with overwrite = 1, should be successful
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'POST'],
    ['--form', "file=@$file1"],
    ['--form', "name=$name1"],
    ['--form', "overwrite=1"],
    ['', "$api_url/file"]
]);
my_assert(json_decode($output, true) == ['status' => 'ok', 'data' => ['name' => $name1]]);

// download a file, confirm the same file
$mktemp = run_cmd('mktemp');
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'GET'],
    ['-o', $mktemp],
    ['', "$api_url/file?" . http_build_query(['name' => $name1])]
]);
my_assert(md5(file_get_contents($mktemp)) == md5(file_get_contents($file1)));

echo "Testing DownloadFile API\n";
// download a non-existing file, should return 404 error
$output = run_cmd('curl', [
    ['-s'],
    ['-I'],
    ['--request', 'GET'],
    ['', "$api_url/file?" . http_build_query(['name' => $name2])]
]);
my_assert(strpos($output, 'HTTP/1.1 404 Not Found') !== false);

// download a file, confirm the same file
$mktemp = run_cmd('mktemp');
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'GET'],
    ['--form', "overwrite=1"],
    ['-o', $mktemp],
    ['', "$api_url/file?" . http_build_query(['name' => $name1])]
]);
my_assert(md5(file_get_contents($mktemp)) == md5(file_get_contents($file1)));

echo "Testing DeleteFile API\n";
// delete a non-existing file, should return not_found error
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'DELETE'],
    ['', "$api_url/file?" . http_build_query(['name' => $name2])]
]);
$output_object = json_decode($output, true);
my_assert($output_object['status'] == 'error' && $output_object['error_code'] == 'not_found');

// delete an existing file, should be successful
$output = run_cmd('curl', [
    ['-s'],
    ['--request', 'DELETE'],
    ['', "$api_url/file?" . http_build_query(['name' => $name1])]
]);
my_assert(json_decode($output, true) == ['status' => 'ok']);
$output = run_cmd('curl', [
    ['-s'],
    ['-I'],
    ['--request', 'GET'],
    ['', "$api_url/file?" . http_build_query(['name' => $name1])]
]);
my_assert(strpos($output, 'HTTP/1.1 404 Not Found') !== false);

echo "Testing if 'similar' files are not stored separately\n";
// uploading a file as 2 different names, disk usage should not change
$output1 = run_cmd('curl', [
    ['-s'],
    ['--request', 'POST'],
    ['--form', "file=@$file2"],
    ['--form', "name=$name1"],
    ['', "$api_url/file"]
]);
$output2 = run_cmd('vagrant ssh', [
    ['-c', 'du -bc /home/vagrant/file-api-master/uploaded-files | grep total | cut -f1']
]);
$output3 = run_cmd('curl', [
    ['-s'],
    ['--request', 'POST'],
    ['--form', "file=@$file2"],
    ['--form', "name=$name2"],
    ['', "$api_url/file"]
]);
$output4 = run_cmd('vagrant ssh', [
    ['-c', 'du -bc /home/vagrant/file-api-master/uploaded-files | grep total | cut -f1']
]);
my_assert($output4 == $output2 &&
    json_decode($output1, true) == ['status' => 'ok', 'data' => ['name' => $name1]] &&
    json_decode($output3, true) == ['status' => 'ok', 'data' => ['name' => $name2]]);

// clean up
foreach ([$name1, $name2, $name3, $name4] as $name) {
    run_cmd('curl', [
        ['-s'],
        ['--request', 'DELETE'],
        ['', "$api_url/file?" . http_build_query(['name' => $name])]
    ]);
}

function my_assert($bool) {
    echo ($bool ? "ok" : "fail"), "\n";
}

function run_cmd($cmd, $args = null) {
    if (!$args || !is_array($args)) {
        $args = [];
    }
    $escaped_args = [];
    foreach ($args as $arg) {
        $arg_string = '';
        if ($arg) {
            $tmp = [];
            for ($i = 0; $i < count($arg); $i++) {
                if ($i == 0) {
                    if ($arg[$i]) {
                        $tmp[] = $arg[$i];
                    }
                } else {
                    $tmp[] = escapeshellarg($arg[$i]);
                }
            }
            $arg_string = implode(' ', $tmp);
        }
        if ($arg_string) {
            $escaped_args[] = $arg_string;
        }
    }
    $cmd_string = escapeshellcmd($cmd) . ' ' . implode(' ', $escaped_args) . ' 2>/dev/null';
    exec($cmd_string, $output);
    return implode("\n", $output);
}