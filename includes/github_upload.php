<?php
/**
 * GitHub File Uploader Helper
 * Uploads a file to a GitHub repo via the GitHub Contents API
 * and returns the raw CDN URL for storage in the database.
 */

define('GITHUB_OWNER', 'mdnahidul337');
define('GITHUB_REPO',  'file-upload');
define('GITHUB_TOKEN', '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');
define('GITHUB_BRANCH','main');

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
    $content  = base64_encode(file_get_contents($tmp_path));
    $api_path = $folder . '/' . $filename;
    $url      = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . $api_path;

    // Check if file already exists (to get its SHA for update)
    $sha = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
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
        'branch'  => GITHUB_BRANCH,
    ];
    if ($sha) $body['sha'] = $sha;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
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
        $raw_url = $response['content']['download_url'];
        // Also build jsDelivr alternative: https://cdn.jsdelivr.net/gh/owner/repo@branch/path
        $cdn_url = "https://cdn.jsdelivr.net/gh/" . GITHUB_OWNER . "/" . GITHUB_REPO . "@" . GITHUB_BRANCH . "/" . $api_path;
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
    // Extract path from URL
    // cdn.jsdelivr.net/gh/owner/repo@branch/folder/file  → folder/file
    if (preg_match('#@[^/]+/(.+)$#', $cdn_url, $m)) {
        $api_path = $m[1];
    } else {
        return false;
    }

    $url = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . $api_path;

    // Get SHA
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
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
        'branch'  => GITHUB_BRANCH,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . GITHUB_TOKEN,
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
            'User-Agent: SCC-Club-App',
        ],
    ]);
    $http_code = curl_getinfo(curl_exec($ch) ? $ch : $ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code === 200;
}
