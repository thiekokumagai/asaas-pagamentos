<?php 
define('ASAAS_URL_HOMOLOGACAO', 'https://api-sandbox.asaas.com/v3');
define('ASAAS_URL', 'https://api.asaas.com/v3');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function asaas_get_options() {
    $options_cadastrados = get_option('asaas_pagamentos_options', []);    
    $environment = '';
    $url = '';
    if (!empty($options_cadastrados['environment']) && $options_cadastrados['environment'] == 'homologacao') {
        $environment = '_' . $options_cadastrados['environment'];
        $url = ASAAS_URL_HOMOLOGACAO;
    } else {
        $url = ASAAS_URL;
    }
    $tokenKey = 'token' . $environment;
    $token = !empty($options_cadastrados[$tokenKey]) ? $options_cadastrados[$tokenKey] : '';
    return [
        "token" => $token,
        "url" => $url,
    ];
}

function asaas_criar_cliente($name, $cpfcnpj, $email, $address, $addressnumber, $complement, $province, $postalcode, $groupname) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }

    $cpfcnpj = preg_replace('/[^\d]/', '', $cpfcnpj);
    $mobilephone = preg_replace('/[^\d]/', '', $mobilephone);
    $postalcode = preg_replace('/[^\d]/', '', $postalcode);
    $apiUrl = $options['url'] . '/customers';
    $accessToken = $options['token'];
    
    $data = [
        'name' => $name,
        'cpfCnpj' => $cpfcnpj,
        'email' => $email,
        'address' => $address,
        'addressNumber' => $addressnumber,
        'complement' => $complement,
        'province' => $province,
        'postalCode' => $postalcode,
        'groupName' => $groupname
    ];
    $client = new Client();    
    try {
        $response = $client->post($apiUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'access_token' => $accessToken,
            ],
            'json' => $data, 
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return json_encode($responseBody, JSON_PRETTY_PRINT);
        
    } catch (RequestException $e) {
        $errorResponse = ['error' => 'Erro desconhecido'];
        if ($e->hasResponse()) {
            $errorResponse = [
                'error' => 'Erro na API',
                'message' => (string) $e->getResponse()->getBody(),
                'status_code' => $e->getResponse()->getStatusCode()
            ];
        } else {
            $errorResponse = [
                'error' => 'Erro na requisição',
                'message' => $e->getMessage()
            ];
        }
        return json_encode($errorResponse, JSON_PRETTY_PRINT);
    }
}
function asaas_criar_pagamento($customer, $value, $dueDate, $description) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }
    $apiUrl = $options['url'] . '/payments';
    $accessToken = $options['token'];    
    $data = [
        'billingType' => 'UNDEFINED', 
        'customer' => $customer, 
        'value' => $value, 
        'dueDate' => $dueDate, 
        'description' => $description, 
        "split"=> [
            [
                "percentualValue"=> 2,
                "walletId"=> "560c1ae1-1cf6-4145-80c4-40c50ea5137c"
            ],
        ],
    ];
    $client = new Client();    
    try {
        $response = $client->post($apiUrl, [
            'headers' => [
                'Accept' => 'application/json', 
                'Content-Type' => 'application/json',
                'access_token' => $accessToken,
            ],
            'json' => $data, 
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return json_encode($responseBody, JSON_PRETTY_PRINT);
        
    } catch (RequestException $e) {
        return  $e->getResponse()->getBody();
    }
}
function asaas_aplicar_cupom($paymentid, $type,$value) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }
    $apiUrl = $options['url'] . '/payments/' . $paymentid;
    $accessToken = $options['token'];
    $data = [       
        'discount' => [
            'type' => $type,
            'value' => $value
        ]
    ];

    $client = new Client();    
    try {
        $response = $client->put($apiUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'access_token' => $accessToken,
            ],
            'json' => $data, 
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return json_encode($responseBody, JSON_PRETTY_PRINT);
        
    } catch (RequestException $e) {
        return  $e->getResponse()->getBody();
    }
}
function asaas_pagar_cartao($paymentid, $holdername, $cardnumber, $expirymonth, $expiryyear, $ccv,$name,$email,$cpfcnpj,$phone,$postalcode,$addressnumber) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }
    $apiUrl = $options['url'] . '/payments/' . $paymentid . '/payWithCreditCard';
    $accessToken = $options['token'];
    $cardnumber = preg_replace('/[^\d]/', '', $cardnumber);
    $expirymonth = preg_replace('/[^\d]/', '', $expirymonth);
    $expiryyear = preg_replace('/[^\d]/', '', $expiryyear);
    $ccv = preg_replace('/[^\d]/', '', $ccv);
    $postalcode = preg_replace('/[^\d]/', '', $postalcode);
    $phone = preg_replace('/[^\d]/', '', $phone);
    $cpfcnpj = preg_replace('/[^\d]/', '', $cpfcnpj);
    
    $creditCardData = [
        'creditCard' => [
            'holderName' => $holdername,
            'number' => $cardnumber,
            'expiryMonth' => $expirymonth,
            'expiryYear' => $expiryyear,
            'ccv' => $ccv
        ],'creditCardHolderInfo' => [
            'name' => $name,
            'email' => $email,
            'cpfCnpj' => $cpfcnpj,
            'mobilePhone' => $phone,
            'postalCode' => $postalcode,
            'addressNumber'=> $addressnumber
        ]
    ];
    $client = new Client();    
    try {
        $response = $client->post($apiUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'access_token' => $accessToken,
            ],
            'json' => $creditCardData,
        ]);
        $responseBody = json_decode($response->getBody(), true);
        return json_encode($responseBody, JSON_PRETTY_PRINT);

    } catch (RequestException $e) {
        return  $e->getResponse()->getBody();
    }
}
function asaas_gerar_pix_qrcode($paymentid) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }
    $apiUrl = $options['url'] . '/payments/' . $paymentid . '/pixQrCode';
    $accessToken = $options['token'];

    $client = new Client();    
    try {
        // Enviando requisição GET para gerar o QR Code Pix
        $response = $client->get($apiUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'access_token' => $accessToken,
            ],
        ]);
        $responseBody = json_decode($response->getBody(), true);
        if (isset($responseBody['success']) && $responseBody['success']==1) {           
            return json_encode($responseBody, JSON_PRETTY_PRINT);
        } else {
            return 'Erro ao gerar o QR Code Pix. Resposta da API';
        }

    } catch (RequestException $e) {
        return  $e->getResponse()->getBody();
    }
}
add_shortcode('asaas_criar_cliente', function($atts) {
    $atts = shortcode_atts([
        'name' => '',
        'cpfcnpj' => '',
        'email' => '',
        'address' => '',
        'addressnumber' => '',
        'complement' => '',
        'province' => '',
        'postalcode' => '',
        'groupname' => '',
    ], $atts);
    $resultado = asaas_criar_cliente(
        esc_attr($atts['name']),
        esc_attr($atts['cpfcnpj']),
        esc_attr($atts['email']),
        esc_attr($atts['address']),
        esc_attr($atts['addressnumber']),
        esc_attr($atts['complement']),
        esc_attr($atts['province']),
        esc_attr($atts['postalcode']),
        esc_attr($atts['groupname'])
    );
    
    return $resultado;
});
function asaas_criar_webhook($url) {
    $options = asaas_get_options(); 
    if (empty($options['token'])) {
        return 'Erro: Token de API não encontrado.';
    }
    $apiUrl = $options['url'] . '/webhooks';
    $accessToken = $options['token'];
    //pegar email do administrador
    $admin_email = get_option('admin_email');
    //pegar nome do site
    // colocar uma data e hora no nome do webhook
    $site_name = get_bloginfo('name') . ' - ' . date('Y-m-d H:i:s');
    $data = [
        'name' => $site_name,
        'url' => $url,
        'enabled' => true,
        'email' => $admin_email,
        'interrupted' => false,
        'authToken' => null,
        'sendType' => 'SEQUENTIALLY',
        'events' => [
            'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED',
            'PAYMENT_CHECKOUT_VIEWED',
            'PAYMENT_BANK_SLIP_VIEWED',
            'PAYMENT_DUNNING_REQUESTED',
            'PAYMENT_DUNNING_RECEIVED',
            'PAYMENT_AWAITING_CHARGEBACK_REVERSAL',
            'PAYMENT_CHARGEBACK_DISPUTE',
            'PAYMENT_CHARGEBACK_REQUESTED',
            'PAYMENT_RECEIVED_IN_CASH_UNDONE',
            'PAYMENT_REFUND_IN_PROGRESS',
            'PAYMENT_REFUNDED',
            'PAYMENT_RESTORED',
            'PAYMENT_DELETED',
            'PAYMENT_OVERDUE',
            'PAYMENT_ANTICIPATED',
            'PAYMENT_RECEIVED',
            'PAYMENT_CONFIRMED',
            'PAYMENT_UPDATED',
            'PAYMENT_CREATED',
            'PAYMENT_REPROVED_BY_RISK_ANALYSIS',
            'PAYMENT_APPROVED_BY_RISK_ANALYSIS',
            'PAYMENT_AWAITING_RISK_ANALYSIS',
            'PAYMENT_AUTHORIZED'
        ]
    ];
    $client = new Client();    
    try {
        $response = $client->post($apiUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'access_token' => $accessToken,
            ],
            'json' => $data, 
        ]);
        $responseBody = json_decode($response->getBody(), true);
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Webhook configurado com sucesso!',
            'data' => [
                'response' => json_encode($responseBody, JSON_PRETTY_PRINT),
                'info' => 'Configuração do webhook foi realizada com sucesso.',
                'webhookUrl' => $url,
            ],
        ], 200);        
    } catch (RequestException $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao configurar webhook.',
            'error' => [
                'message' => $e->getMessage(),
            ],

        ], 500);
    }
}
add_shortcode('asaas_criar_pagamento', function($atts) {
    $atts = shortcode_atts([
        'customer' => '',
        'value' => '',
        'duedate' => '',
        'description' => '',
    ], $atts);
    if (empty($atts['customer']) || empty($atts['value']) || empty($atts['duedate'])) {
        return '<p>Erro: Todos os campos obrigatórios devem ser preenchidos.</p>';
    }

    // Chama a função para criar o pagamento
    $resultado = asaas_criar_pagamento(
        esc_attr($atts['customer']),
        esc_attr($atts['value']),
        esc_attr($atts['duedate']),
        esc_attr($atts['description'])
    );
    
    return $resultado;
});
add_shortcode('asaas_aplicar_cupom', function($atts) {
    $atts = shortcode_atts([
        'paymentid' => '',
        'type' => '',
        'value' => '',
    ], $atts);
    if (empty($atts['paymentid']) || empty($atts['type']) || empty($atts['value'])) {
        return '<p>Erro: Todos os campos obrigatórios devem ser preenchidos.</p>';
    }
    $resultado = asaas_aplicar_cupom(
        esc_attr($atts['paymentid']),
        esc_attr($atts['type']),
        esc_attr($atts['value'])
    );
    return $resultado;
});
add_shortcode('asaas_pagar_cartao', function($atts) {
    $atts = shortcode_atts([
        'paymentid' => '',
        'holdername' => '',
        'cardnumber' => '',
        'expirymonth' => '',
        'expiryyear' => '',
        'ccv' => '',
        'name' => '',
        'email' => '',
        'cpfcnpj' => '',
        'phone' => '',
        'postalcode' => '',
        'addressnumber' => '',
    ], $atts);
    $resultado = asaas_pagar_cartao(
        esc_attr($atts['paymentid']),
        esc_attr($atts['holdername']),
        esc_attr($atts['cardnumber']),
        esc_attr($atts['expirymonth']),
        esc_attr($atts['expiryyear']),
        esc_attr($atts['ccv']),
        esc_attr($atts['name']),
        esc_attr($atts['email']),
        esc_attr($atts['cpfcnpj']),
        esc_attr($atts['phone']),
        esc_attr($atts['postalcode']),
        esc_attr($atts['addressnumber'])
    );
    return $resultado;
});
add_shortcode('asaas_gerar_pix_qrcode', function($atts) {
    $atts = shortcode_atts([
        'paymentid' => '',
    ], $atts);
    $resultado = asaas_gerar_pix_qrcode(esc_attr($atts['paymentid']));
    return $resultado;
});