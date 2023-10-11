<?php
 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function seogen_admin_menu() {
    add_menu_page(
        'SEO Article Generator', 
        'Article Generator', 
        'manage_options', 
        'seo-article-generator', 
        'seogen_admin_page_content', 
        'dashicons-admin-page'
    );
}
add_action('admin_menu', 'seogen_admin_menu');
function seogen_admin_page_content() {
?>    
    <div class="wrap">
        <h2>SEO Friendly Article Generator By Shailesh</h2>

        <form method="post" action="">
            <h3>API Key Settings</h3>
            <label for="api_key">OpenAI API Key:</label>
            <input type="text" name="api_key" value="<?php echo esc_attr(get_option('seogen_api_key')); ?>" required>
            <input type="submit" name="save_api_key" value="Save API Key">
        </form>

        <form method="post" action="">
            <h3>Generate Article</h3>
            <label for="keyword">Keyword/Keyphrase:</label>
            <input type="text" name="keyword" id="keyword" required>
            <input type="submit" name="generate" value="Generate Article">
        </form>
        
        <?php
        if (isset($_POST['save_api_key'])) {
            update_option('seogen_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="updated"><p>API Key saved successfully!</p></div>';
        }

        if (isset($_POST['generate'])) {
            $article = seogen_generate_article($_POST['keyword']);
            echo '<div class="generated-article">' . $article . '</div>';
        }
        ?>
    </div>
<?php    
}

function seogen_generate_article($keyword) {
    $api_key = get_option('seogen_api_key'); // Get API key from WP options
    if (empty($api_key)) {
        return "Error: API key not set. Please provide your OpenAI API key.";
    }

    $endpoint = 'https://api.openai.com/v1/engines/gpt-3.5-turbo/createChatCompletion';  // Note: Assuming GPT-3.5's endpoint is 'gpt-3.5-turbo'
    
    $prompt = "As an experienced copywriter, write a comprehensive, SEO-optimized blog post for the keyword $keyword with neutral and persuasive tone and a desired length of 2000-3000 words.";
    #$prompt = "Write a 500-word SEO-friendly article about $keyword.";

    $data = [
        'prompt' => $prompt,
        'max_tokens' => 1000  // adjust as needed
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);

    try {
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Curl Error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            $errorMessage = 'OpenAI returned HTTP code ' . $httpCode;
            if (isset($response_data['error'])) {
                $errorMessage .= ': ' . $response_data['error']['message'];
            }
            throw new Exception($errorMessage);
        }

        $response_data = json_decode($response, true);

        if (!isset($response_data['choices'][0]['text'])) {
            throw new Exception('Unexpected API response format.');
        }

        return $response_data['choices'][0]['text'];
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    } finally {
        curl_close($ch);
    }
}

?>
