<?php
$autoload = realpath(__DIR__ . '/vendor/autoload.php');
if (!file_exists($autoload)) {
    die("Autoload file not found or on path <code>$autoload</code>.");
}
require_once $autoload;
include_once plugin_dir_path(__FILE__) . 'includes/asaas-functions.php';
/*
Plugin Name: Asaas Pagamentos
Description: Plugin para configurar os campos de pagamento Asaas.
Version: 1.0e
Author: Thieko Kumagai
*/
defined('ABSPATH') || exit;
function asaas_pagamentos_create_menu() {
    add_menu_page(
        'Asaas Pagamentos',
        'Asaas Pagamentos',
        'manage_options',
        'Asaas-pagamentos',
        'asaas_pagamentos_settings_page',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'asaas_pagamentos_create_menu');
function asaas_pagamentos_allow_p12_upload($mime_types) {
    $mime_types['p12'] = 'application/x-pkcs12';
    return $mime_types;
}
add_filter('upload_mimes', 'asaas_pagamentos_allow_p12_upload');
// Página de configurações do plugin
function asaas_pagamentos_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações Asaas Pagamentos</h1>
        <button id="create-webhook-btn" style="margin-top:10px;" class="button">Criar Webhook</button>
        <div id="webhook-status" style="margin-top:10px;"></div>
        <script>
            document.getElementById('create-webhook-btn').addEventListener('click', function() {
                const statusDiv = document.getElementById('webhook-status');
                statusDiv.innerHTML = 'Criando o Webhook...';
                fetch('<?php echo esc_url(rest_url('asaas/v1/criar-webhook')); ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = 'Webhook criado com sucesso!';
                            // Salvar a URL do Webhook nas opções
                            const webhookUrl = data.data.webhookUrl;
                            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    action: 'save_webhook_url',
                                    webhook_url: webhookUrl
                                })
                            })
                            .then(response => response.json())
                            .then(saveData => {
                                if (saveData.success) {
                                    alert('Webhook URL salva nas configurações');
                                    location.reload();
                                }
                            });
                        } else {
                            statusDiv.innerHTML = 'Erro ao criar Webhook: ' + data.message;
                        }
                    })
                    .catch(error => {
                        statusDiv.innerHTML = 'Erro: ' + error.message;

                    });
            });
        </script>
        <form method="post" action="options.php" style="margin-top:20px;" enctype="multipart/form-data">
            <?php
            settings_fields('asaas_pagamentos_settings');
            do_settings_sections('Asaas-pagamentos');
            submit_button();
            ?>
        </form>   
    </div>
    <?php
}
// Registrar configurações
function asaas_pagamentos_settings_init() {
    register_setting('asaas_pagamentos_settings', 'asaas_pagamentos_options');
    add_settings_section(
        'assas_post_section',
        'Vínculo com post',
        null,
        'Asaas-pagamentos'
    );
    $post_fields = [        
        'tipo_post' => 'Tipo de Post',
        'id_pagamento'=>'ID Pagamento',
        'installment'=>'ID Parcelamento',
        'data_pagamento'=>'Data de Pagamento',
        'status'=>'Status Pagamento',  
        'pago'=>'Status Controle'
    ];
    foreach ($post_fields as $field => $label) {
        add_settings_field(
            $field,
            $label,
            'asaas_pagamentos_field_callback',
            'Asaas-pagamentos',
            'assas_post_section',
            ['label_for' => $field]
        );
    }
    add_settings_section(
        'asaas_pagamentos_section',
        'Credenciais',
        null,
        'Asaas-pagamentos'
    );
    $fields = [   
        'token' => 'Token Produção',
        'token_homologacao' => 'Token Homologação',       
        'environment' => 'Ambiente',
        'webhook'=>'Webhook',
    ];
    foreach ($fields as $field => $label) {
        add_settings_field(
            $field,
            $label,
            'asaas_pagamentos_field_callback',
            'Asaas-pagamentos',
            'asaas_pagamentos_section',
            ['label_for' => $field]
        );
    }
    
}
add_action('admin_init', 'asaas_pagamentos_settings_init');
// Callback para renderizar os campos 
function asaas_pagamentos_field_callback($args) {
    $options = get_option('asaas_pagamentos_options', []);
    $field = $args['label_for'];
    if ($field === 'webhook') {
        $value = isset($options[$field]) ? $options[$field] : '';
        echo "<p>$value</p>";
        echo "<input type='hidden' id='$field' name='asaas_pagamentos_options[$field]' value='$value' class='regular-text'  readonly/>";
    }elseif ($field === 'environment') {
        $value = isset($options[$field]) ? $options[$field] : 'producao';
        echo "<select id='$field' name='asaas_pagamentos_options[$field]'>  
                <option value='homologacao' " . selected($value, 'homologacao', false) . ">Homologação</option>
                <option value='producao' " . selected($value, 'producao', false) . ">Produção</option>
              </select>";
    } elseif ($field === 'timeout') {
        $value = isset($options[$field]) ? $options[$field] : 30;
        echo "<input type='number' id='$field' name='asaas_pagamentos_options[$field]' value='$value' class='regular-text' min='1' />";
        echo "<p class='description'>Tempo limite em segundos (padrão: 30).</p>";
    } elseif ($field === 'tipo_post') {
        $value = isset($options[$field]) ? $options[$field] : '';
        $post_types = get_post_types(['public' => true], 'objects'); 
        echo "<select id='tipo_post' name='asaas_pagamentos_options[$field]'>";
        foreach ($post_types as $post_type => $post_type_obj) {
            echo "<option value='$post_type' " . selected($value, $post_type, false) . ">{$post_type_obj->labels->name}</option>";
        }
        echo "</select>";
    }else {
        $value = isset($options[$field]) ? $options[$field] : '';
        echo "<input type='text' id='$field' name='asaas_pagamentos_options[$field]' value='$value' class='regular-text' />";
    }
}
// Gerencia o upload do certificado de produção
function asaas_pagamentos_admin_notices() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        add_settings_error(
            'asaas_pagamentos_messages', 
            'asaas_pagamentos_success', 
            'Configurações salvas com sucesso!', 
            'updated' 
        );
    }
    settings_errors('asaas_pagamentos_messages');
}
add_action('admin_notices', 'asaas_pagamentos_admin_notices');

// Função para excluir arquivos

function asaas_pagamentos_save_webhook_url() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }
    $webhook_url = isset($_POST['webhook_url']) ? sanitize_text_field($_POST['webhook_url']) : '';
    if (!$webhook_url) {
        wp_send_json_error(['message' => 'URL do Webhook não recebida.']);
    }
    $options = get_option('asaas_pagamentos_options', []);
    $options['webhook'] = $webhook_url;
    if (update_option('asaas_pagamentos_options', $options)) {
        wp_send_json_success(['message' => 'URL do Webhook salva com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao salvar a URL do Webhook.']);
    } 

}
add_action('wp_ajax_save_webhook_url', 'asaas_pagamentos_save_webhook_url');
add_action('rest_api_init', function () {
    error_log('rest_api_init chamado!');
    register_rest_route('asaas/v1', '/criar-webhook', [
        'methods' => 'GET',
        'callback' => 'asaas_criar_webhook_callback',
        'permission_callback' => '__return_true', 
    ]);
    register_rest_route('asaas/v1', '/webhook', [
        'methods' => ['POST', 'GET'],
        'callback' => 'asaas_webhook_callback',
        'permission_callback' => '__return_true', 
    ]);
});

function asaas_criar_webhook_callback(WP_REST_Request $request) {
    error_log('Rota /criar-webhook!');
    $response = asaas_criar_webhook(get_bloginfo('url') . "/wp-json/asaas/v1/webhook");
    return $response;
}
function asaas_webhook_callback(WP_REST_Request $request) {
    error_log('Rota /webhook chamada!');
    
    $options = get_option('asaas_pagamentos_options', []);
    $dados_webhook = json_decode(file_get_contents("php://input"));
    
    if (!isset($dados_webhook->payment)) {
        return;
    }
    
    $payment = $dados_webhook->payment;
    $status = $payment->status;
    $forma_pagamento = $payment->billingType;
    $foi_pago = in_array($status, ['CONFIRMED', 'RECEIVED']);
    $vencido = in_array($status, ['OVERDUE', 'CANCELLED', 'REFUNDED', 'FAILED']);
    $data_pagamento = $payment->confirmedDate ?? null;
    
    if ($forma_pagamento === 'PIX' && isset($payment->pixTransaction)) {
        $data_pagamento = $payment->pixTransaction->receivedDate;
    }
    
    $meta_key = isset($payment->installment) ? $options['installment'] : $options['id_pagamento'];
    $meta_value = isset($payment->installment) ? $payment->installment : $payment->id;
    
    $wp_query = new WP_Query([
        'post_type'      => $options['tipo_post'],
        'order'          => 'DESC',
        'posts_per_page' => 1,
        'meta_query'     => [['key' => $meta_key, 'compare' => '=', 'value' => $meta_value]]
    ]);
    
    if ($wp_query->have_posts()) {
        while ($wp_query->have_posts()) {
            $wp_query->the_post();
            $post_id = get_the_ID();
            $cupom_desconto = get_field('cupom_desconto', $post_id);
            
            if ($foi_pago) {
                atualizar_post_pago($post_id, $options, $status, $forma_pagamento, $data_pagamento, $cupom_desconto, isset($payment->installment));
            }else{
                if(isset($payment->installment)){
                    update_post_meta($post_id, $options['status'], '');
                    update_post_meta($post_id, $options['pago'], false);
                }
            }
            
            if ($vencido) {
                marcar_post_como_rascunho($post_id);
            }
        }
    }
    
    wp_reset_query();
}

function atualizar_post_pago($post_id, $options, $status, $forma_pagamento, $data_pagamento, $cupom_desconto, $is_installment) {
    update_post_meta($post_id, $options['status'], $status);
    update_post_meta($post_id, 'forma_pagamento', $forma_pagamento);
    update_post_meta($post_id, $options['data_pagamento'], $data_pagamento);
    update_post_meta($post_id, $options['pago'], true);
    
    if ($is_installment) {
        asaas_cancelar_pagamento(get_field('id_pagamento', $post_id));
        update_post_meta($post_id, $options['id_pagamento'], '');
    }
    
    if ($cupom_desconto) {
        $utilizados = get_field('utilizados', $cupom_desconto) + 1;
        update_post_meta($cupom_desconto, 'utilizados', $utilizados);
    }
}

function marcar_post_como_rascunho($post_id) {
    wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);    
    
}


