<div class="wrap">

<h1>Pulse Storm Launcher Settings</h1>

<form method="post" action="options.php"> 

<?php
    settings_fields( 'pulsestorm_launcher-group' );
    do_settings_sections( 'pulsestorm_launcher-group' );
?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                Launcher Hot Key (Ctrl + ?)
            </th>
            <td>
                <input type="text" name="pulsestorm_launcher_trigger_key" 
                    value="<?php echo esc_attr( get_option('pulsestorm_launcher_trigger_key',Pulsestorm_Launcher_Plugin::DEFAULT_TRIGGER_KEY) ); ?>" 
                    maxlength="1"
                    />
            </td>
        </tr>
    </table>
<?php submit_button(); ?>
</form>
</div>