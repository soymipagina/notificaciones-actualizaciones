<?php
/**
 * Plugin Name: Notificaciones por Actualizaciones
 * Description: Envía correos al cliente y al administrador solo cuando se actualizan plugins, temas o core exitosamente.
 * Version: 1.7
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

// Panel de configuración
add_action('admin_menu', function() {
    add_options_page('Notificaciones de Actualizaciones', 'Notificaciones WP', 'manage_options', 'notificaciones-wp', 'notificaciones_wp_config_page');
});

function notificaciones_wp_config_page() {
    ?>
    <div class="wrap">
        <h2>Correos del cliente</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('notificaciones_wp_opciones');
            do_settings_sections('notificaciones_wp');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('notificaciones_wp_opciones', 'notificaciones_wp_email');
    add_settings_section('notificaciones_wp_seccion', '', null, 'notificaciones_wp');
    add_settings_field('notificaciones_wp_email', 'Correos del cliente:', 'notificaciones_wp_email_callback', 'notificaciones_wp', 'notificaciones_wp_seccion');
});

function notificaciones_wp_email_callback() {
    $value = get_option('notificaciones_wp_email', '');
    echo '<input type="text" name="notificaciones_wp_email" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Puedes ingresar varios correos separados por comas.</p>';
}

// API para integración con MainWP
add_action('rest_api_init', function () {
    register_rest_route('notificaciones-wp/v1', '/correo-cliente', array(
        'methods' => 'GET',
        'callback' => function () {
            return ['email' => get_option('notificaciones_wp_email', '')];
        },
        'permission_callback' => '__return_true'
    ));
});

// Hook principal
add_action('upgrader_process_complete', 'notificaciones_wp_enviar_email', 10, 2);

function notificaciones_wp_enviar_email($upgrader_object, $options) {
    $accion = $options['action'] ?? '';
    $tipo = $options['type'] ?? '';
    if ($accion !== 'update') return;

    $correo_cliente = get_option('notificaciones_wp_email');
    $correo_admin = get_option('admin_email');
    if ((!$correo_cliente || !$correo_admin)) return;

    $sitio = get_bloginfo('name');
    $hora = date('Y-m-d H:i:s');
    $ip = $_SERVER['SERVER_ADDR'] ?? 'IP no disponible';
    $logo = 'https://soymipagina.com/wp-content/uploads/2022/02/logo_transparent_background.svg';

    $actualizados = [];

    if ($tipo === 'plugin') {
        $resultados = $upgrader_object->result['plugins'] ?? [$upgrader_object->result['plugin'] ?? null];
        foreach ($resultados as $ruta_plugin) {
            if ($ruta_plugin) {
                $slug = explode('/', $ruta_plugin)[0];
                $nombre_legible = ucwords(str_replace(['-', '_'], ' ', $slug));
                $actualizados[] = $nombre_legible;
            }
        }
    } elseif ($tipo === 'theme' && !empty($upgrader_object->result['themes'])) {
        foreach ($upgrader_object->result['themes'] as $tema) {
            $slug = explode('/', $tema)[0];
            $nombre_legible = ucwords(str_replace(['-', '_'], ' ', $slug));
            $actualizados[] = $nombre_legible;
        }
    } elseif ($tipo === 'core' && !empty($upgrader_object->result['destination_name'])) {
        $actualizados[] = 'WordPress ' . $upgrader_object->result['destination_name'];
    }

    if (empty($actualizados)) return;

    $lista_actualizados = "<ul>";
    foreach ($actualizados as $nombre_legible) {
        $lista_actualizados .= "<li>" . esc_html($nombre_legible) . "</li>";
    }
    $lista_actualizados .= "</ul>";

    $mensaje_cliente = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;'>
            <img src='$logo' alt='Soymipagina' style='max-width: 180px; margin-bottom: 20px;'>
            <p>Estimado cliente,</p>
            <p>Como parte de nuestra rutina de seguridad, optimización y actualización de los sitios web que administramos, le informamos que su sitio <strong>$sitio</strong> ha sido actualizado correctamente.</p>
            <p>Elementos actualizados:</p>
            $lista_actualizados
            <p>Si nota algo inusual o tiene alguna duda, puede escribirnos a <a href='mailto:soporte@soymipagina.com'>soporte@soymipagina.com</a>.</p>
            <p style='margin-top: 40px;'>Saludos cordiales,<br>Equipo de Soymipagina</p>
        </div>
    ";

    $mensaje_interno = "Se ha realizado una actualización en el sitio: $sitio\n\n";
    $mensaje_interno .= "Elementos actualizados:\n" . implode("\n", $actualizados) . "\n\n";
    $mensaje_interno .= "---\nDetalles técnicos:\n";
    $mensaje_interno .= "Hora del evento: $hora\n";
    $mensaje_interno .= "Tipo de actualización: $tipo\n";
    $mensaje_interno .= "Acción ejecutada: $accion\n";
    $mensaje_interno .= "IP del servidor: $ip\n";
    $mensaje_interno .= "---\nEste mensaje es para fines de control interno.";

    $asunto_cliente = "Sitio actualizado: $sitio";
    $asunto_interno = "[Equipo interno] $asunto_cliente";
    $headers_html = ['Content-Type: text/html; charset=UTF-8'];

    $correos_cliente = explode(',', $correo_cliente);
    foreach ($correos_cliente as $correo) {
        $correo = trim($correo);
        if (is_email($correo)) {
            wp_mail($correo, $asunto_cliente, $mensaje_cliente, $headers_html);
        }
    }

    if (is_email($correo_admin)) {
        wp_mail($correo_admin, $asunto_interno, $mensaje_interno);
    }
}
?>
