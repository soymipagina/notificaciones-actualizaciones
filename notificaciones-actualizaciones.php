<?php
/**
 * Plugin Name: Notificaciones por Actualizaciones
 * Description: Envía correos al cliente y al administrador solo cuando se actualizan plugins, temas o core exitosamente.
 * Version: 1.9.8
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

        // 1) Si es un update de plugins
        if ( $options['type'] === 'plugin' ) {
            // bulk (varios plugins)
            if ( ! empty( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
                foreach ( $options['plugins'] as $plugin_file ) {
                    $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
                    $actualizados[] = '🔌 Plugin: ' . $data['Name'];
                }
            }
            // single
            elseif ( ! empty( $options['plugin'] ) ) {
                $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $options['plugin'] );
                $actualizados[] = '🔌 Plugin: ' . $data['Name'];
            }
        }
        
        // 2) Si es un update de temas
        elseif ( $options['type'] === 'theme' ) {
            // bulk (varios temas)
            if ( ! empty( $options['themes'] ) && is_array( $options['themes'] ) ) {
                foreach ( $options['themes'] as $theme_slug ) {
                    $theme = wp_get_theme( $theme_slug );
                    $actualizados[] = '🎨 Tema: ' . $theme->get( 'Name' );
                }
            }
            // single
            elseif ( ! empty( $options['theme'] ) ) {
                $theme = wp_get_theme( $options['theme'] );
                $actualizados[] = '🎨 Tema: ' . $theme->get( 'Name' );
            }
        }
        
        // 3) Si es core
        elseif ( $options['type'] === 'core' ) {
            $actualizados[] = '🛠️ Core de WordPress';
        }
        
        // 4) Fallback si no encontramos nada
        if ( empty( $actualizados ) ) {
            $actualizados[] = '🔄 Componentes desconocidos';
        }
        
        

        $subject = '✅ Sitio actualizado: ' . $sitio;
        $message = '<div style="font-family: sans-serif; font-size: 15px;">';
        $message .= '<p><img src="https://soymipagina.com/wp-content/uploads/2024/11/logo-soymipagina-300x69-1.png" alt="Soymipagina" style="height: 40px;"></p>';
        $message .= "<p>Hola:</p>";
        $message .= "<p>El sitio <strong>'$sitio'</strong> (<a href=\"$url\">$url</a>) acaba de completar una actualización automática de <strong>{$options['type']}</strong>.</p>";
        $message .= "<p><strong>Componentes actualizados:</strong></p><ul>";
        foreach ($actualizados as $item) {
            $message .= "<li>$item</li>";
        }
        $message .= "</ul><p>Revisa que todo esté funcionando correctamente.</p><p>Saludos.</p></div>";

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Enviar al administrador
        wp_mail($admin_email, $subject, $message, $headers);

        // Enviar a todos los correos del cliente (si están configurados)
        if (!empty($cliente_emails)) {
            $correos = array_map('trim', explode(',', $cliente_emails));
            foreach ($correos as $correo) {
                if (is_email($correo)) {
                    wp_mail($correo, $subject, $message, $headers);
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
?>
