<?php
/**
 * Plugin Name: Notificaciones por Actualizaciones
 * Description: Envía correos al cliente y al administrador solo cuando se actualizan plugins, temas o core exitosamente.
 * Version: 1.9.5
 * Author: Luis Fernando
 * Update URI: https://github.com/soymipagina/notificaciones-actualizaciones
 */

add_action('plugins_loaded', function () {
    $checker_path = plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    if (file_exists($checker_path)) {
        require_once $checker_path;
    } else {
        error_log("❌ No se encontró plugin-update-checker.php en: " . $checker_path);
        return;
    }

    \YahnisElsts\PluginUpdateChecker\v5p5\PucFactory::buildUpdateChecker(
        'https://github.com/soymipagina/notificaciones-actualizaciones/',
        __FILE__,
        'notificaciones-actualizaciones'
    );
});

// Enlace de configuración desde la lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=notificaciones-wp') . '">Configuración</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Enviar correo cuando se actualicen plugins, temas o core
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if ($options['action'] === 'update' && in_array($options['type'], ['plugin', 'theme', 'core'])) {

        $sitio = get_bloginfo('name');
        $url = get_site_url();
        $admin_email = get_option('admin_email');
        $cliente_emails = get_option('notificaciones_wp_email');

        $actualizados = [];
        if (!empty($upgrader_object->skin->result['plugin'])) {
            $plugin_slug = $upgrader_object->skin->result['plugin'];
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
            $actualizados[] = '🔌 Plugin: ' . $plugin_data['Name'];
        } elseif (!empty($upgrader_object->skin->result['theme'])) {
            $theme = wp_get_theme($upgrader_object->skin->result['theme']);
            $actualizados[] = '🎨 Tema: ' . $theme->get('Name');
        } elseif ($options['type'] === 'core') {
            $actualizados[] = '🛠️ Core de WordPress';
        }

        if (empty($actualizados)) {
            $actualizados[] = '🔄 Componentes desconocidos';
        }

        $subject = '✅ Sitio actualizado: ' . $sitio;
        $message = "Hola:\n\nEl sitio '$sitio' ($url) acaba de completar una actualización automática de {$options['type']}.\n\nComponentes actualizados:\n- " . implode("\n- ", $actualizados) . "\n\nRevisa que todo esté funcionando correctamente.\n\nSaludos.";

        // Enviar al administrador
        wp_mail($admin_email, $subject, $message);

        // Enviar a todos los correos del cliente (si están configurados)
        if (!empty($cliente_emails)) {
            $correos = array_map('trim', explode(',', $cliente_emails));
            foreach ($correos as $correo) {
                if (is_email($correo)) {
                    wp_mail($correo, $subject, $message);
                }
            }
        }
    }
}, 10, 2);

// Panel de configuración
add_action('admin_menu', function() {
    add_options_page('Notificaciones por Actualizaciones', 'Notificaciones WP', 'manage_options', 'notificaciones-wp', 'notificaciones_wp_config_page');
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
    add_settings_field('notificaciones_wp_email', 'Correos del cliente:', function() {
        $value = get_option('notificaciones_wp_email', '');
        echo '<input type="text" name="notificaciones_wp_email" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Puedes ingresar varios correos separados por comas.</p>';
    }, 'notificaciones-wp', 'notificaciones_wp_main');
});
//testing de actualizacion 1.9.5
?>
