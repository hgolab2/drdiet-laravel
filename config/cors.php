<?php
// config/cors.php

return [
    'paths' => ['api/*','profileedit','articles','articles/*', 'login','checkverify','forget','verifymobile1','verifymobile', 'register','getuser','questionnaire','exam','exams/*','get_user_level','save_game_result','users_level','suggest'
,'games/*','gametoken/*','game/*','suggest','game_category','cognitive/*','cognitive1/*','exam/*','questions/*','all_replies','essay-categories','essays','essays/*','settings/*','api/*'
],
'allowed_methods' => ['*'],

'allowed_origins' => ['*'],

'allowed_origins_patterns' => [],

'allowed_headers' => ['*'],

'exposed_headers' => [],

'max_age' => 0,

'supports_credentials' => false,

];
