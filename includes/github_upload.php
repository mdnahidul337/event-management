/**
 * Fetch GitHub Configuration from Settings Table
 */
function get_github_settings() {
    global $pdo;
    if (!isset($pdo)) {
        require_once dirname(__FILE__) . '/db_connect.php';
    }
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'github_%'");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return [
        'owner'  => $settings['github_owner']  ?? 'mdnahidul337',
        'repo'   => $settings['github_repo']   ?? 'file-upload',
        'token'  => $settings['github_token']  ?? '',
        'branch' => $settings['github_branch'] ?? 'main'
    ];
}

/**
 * Upload a local temp file to GitHub and return its raw URL.
 *
 * @param string $tmp_path   Path to the uploaded temp file ($_FILES['x']['tmp_name'])
 * @param string $filename   Desired filename (e.g. 'evt_123.jpg')
 * @param string $folder     Folder inside the repo (e.g. 'events' or 'profiles')
 * @return string|false      Raw GitHub URL on success, false on failure
 */
function github_upload(string $tmp_path, string $filename, string $folder = 'events'): string|false
{
    $gh = get_github_settings();
    if (empty($gh['token'])) return false;

    $content  = base64_encode(file_get_contents($tmp_path));
    $api_path = $folder . '/' . $filename;
    $url      = "https://api.github.com/repos/" . $gh['owner'] . "/" . $gh['repo'] . "/contents/" . $api_path;

    // Check if file already exists (to get its SHA for update)
    $sha = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $gh['token'],
            'Accept: application/vnd.github.v3+json',
            'User-Agent: SCC-Club-App',
        ],
    ]);
    $existing = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (isset($existing['sha'])) $sha = $existing['sha'];

    // Upload (create or update)
    $body = [
        'message' => 'Upload ' . $api_path . ' via SCC Portal',
        'content' => $content,
        'branch'  => $gh['branch'],
    ];
    if ($sha) $body['sha'] = $sha;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $gh['token'],
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
            'User-Agent: SCC-Club-App',
        ],
    ]);
    $response = json_decode(curl_exec($ch), true);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (in_array($http_code, [200, 201]) && isset($response['content']['download_url'])) {
        // Convert to jsDelivr CDN URL for faster loading
        $cdn_url = "https://cdn.jsdelivr.net/gh/" . $gh['owner'] . "/" . $gh['repo'] . "@" . $gh['branch'] . "/" . $api_path;
        return $cdn_url;
    }

    return false;
}

/**
 * Delete a file from GitHub by its stored URL.
 *
 * @param string $cdn_url  The URL stored in DB (cdn.jsdelivr.net or raw.githubusercontent.com)
 * @return bool
 */
function github_delete(string $cdn_url): bool
{
    $gh = get_github_settings();
    if (empty($gh['token'])) return false;

    // Extract path from URL
    // cdn.jsdelivr.net/gh/owner/repo@branch/folder/file  → folder/file
    if (preg_match('#@[^/]+/(.+)$#', $cdn_url, $m)) {
        $api_path = $m[1];
    } else {
        return false;
    }

    $url = "https://api.github.com/repos/" . $gh['owner'] . "/" . $gh['repo'] . "/contents/" . $api_path;

    // Get SHA
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $gh['token'],
            'Accept: application/vnd.github.v3+json',
            'User-Agent: SCC-Club-App',
        ],
    ]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (empty($data['sha'])) return false;

    $body = [
        'message' => 'Delete ' . $api_path . ' via SCC Portal',
        'sha'     => $data['sha'],
        'branch'  => $gh['branch'],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $gh['token'],
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
            'User-Agent: SCC-Club-App',
        ],
    ]);
    $http_code = curl_getinfo(curl_exec($ch) ? $ch : $ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code === 200;
}
