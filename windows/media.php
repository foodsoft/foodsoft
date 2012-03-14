<?php

assert( $angemeldet ) or exit();

need_http_var('id', 'u');

$media = sql_media($id);

header("Content-Type: {$media['mimetype']}");

$content_disposition = "Content-Disposition: inline";

if (!is_null($media['name']))
  $content_disposition .= "; filename=\"{$media['name']}\"";

header($content_disposition);
  
header("Content-Length: ". strlen($media['data']));

echo ($media['data']);

?>
