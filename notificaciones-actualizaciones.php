<?php
/**
 * Plugin Name: Notificaciones por Actualizaciones
 * Description: Envía correos al cliente y al administrador solo cuando se actualizan plugins, temas o core exitosamente.
 * Version: 1.9.1
 * Author: Luis Fernando
 * Update URI: https://github.com/soymipagina/notificaciones-actualizaciones
 */

add_action('plugins_loaded', function () {
    require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/soymipagina/notificaciones-actualizaciones/',
        __FILE__,
        'notificaciones-actualizaciones'
    );
});

// Enviar correo al actualizar plugins, temas o el core
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if ($options['action'] === 'update' && in_array($options['type'], ['plugin', 'theme', 'core'])) {

        $sitio = get_bloginfo('name');
        $url = get_site_url();
        $admin_email = get_option('admin_email');

        $subject = '✅ Sitio actualizado: ' . $sitio;
        $message = "Hola:\n\nEl sitio '$sitio' ($url) acaba de completar una actualización automática de {$options['type']}.\n\nRevisa que todo esté funcionando correctamente.\n\nSaludos.";

        // Enviar al administrador
        wp_mail($admin_email, $subject, $message);

        // Enviar a otro correo si lo deseas
        $otro_correo = get_option('notificaciones_wp_email');
        if ($otro_correo && is_email($otro_correo)) {
            wp_mail($otro_correo, $subject, $message);
        }
    }
}, 10, 2);

// Configuración en el panel de administración (opcional)
add_action('admin_menu', function() {
    add_options_page('Notificaciones de Actualizaciones', 'Notificaciones WP', 'manage_options', 'notificaciones-wp', 'notificaciones_wp_config_page');
});

function notificaciones_wp_config_page() {
    ?>
    <div class="wrap">
        <h2>Notificaciones por Actualizaciones</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields('notificaciones_wp_settings');
                do_settings_sections('notificaciones-wp');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('notificaciones_wp_settings', 'notificaciones_wp_email');
    add_settings_section('notificaciones_wp_main', '', null, 'notificaciones-wp');
    add_settings_field('notificaciones_wp_email', 'Correo adicional para notificaciones', function() {
        $value = get_option('notificaciones_wp_email', '');
        echo '<input type="email" name="notificaciones_wp_email" value="' . esc_attr($value) . '" class="regular-text">';
    }, 'notificaciones-wp', 'notificaciones_wp_main');
});
//Lo de escriba
?>
