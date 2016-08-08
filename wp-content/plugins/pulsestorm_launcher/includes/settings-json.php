<?php
    $this->outputJsonWithScriptTag('pulsestorm_launcher_settings', [
        'pulsestorm_launcher_trigger_key'=>get_option('pulsestorm_launcher_trigger_key'),
        'pulsestorm_launcher_trigger_code'=>ord(strToUpper(get_option('pulsestorm_launcher_trigger_key')))
    ]);
?>

