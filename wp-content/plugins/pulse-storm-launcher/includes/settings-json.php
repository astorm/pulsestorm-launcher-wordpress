<?php
    $this->output_json_with_script_tag('pulsestorm_launcher_settings', [
        'pulsestorm_launcher_trigger_key'=>get_option('pulsestorm_launcher_trigger_key'),
        'pulsestorm_launcher_trigger_code'=>ord(strToUpper(get_option('pulsestorm_launcher_trigger_key')))
    ]);
?>

