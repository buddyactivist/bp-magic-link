<?php
/**
 * Plugin Name: BP Magic Link
 * Description: Registrazione e accesso a BuddyPress tramite Magic Link via Email con PIN di sicurezza per Admin.
 * Version: 1.3
 * Author: BuddyActivist
 * Text Domain: bp-magic-link
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// ==========================================
// 0. CARICAMENTO TRADUZIONI (i18n)
// ==========================================
add_action( 'plugins_loaded', 'bpml_load_textdomain' );

function bpml_load_textdomain() {
    load_plugin_textdomain( 'bp-magic-link', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// ==========================================
// 1. SHORTCODE PER IL FORM DI LOGIN/REGISTRAZIONE
// ==========================================
add_shortcode( 'bp_magic_link', 'bpml_render_form' );

function bpml_render_form() {
    ob_start();
    ?>
    <div class="bpml-form-container">
        
        <?php 
        // Se l'utente è già loggato, mostra il messaggio sopra il form
        if ( is_user_logged_in() ) : 
        ?>
            <p style="font-weight: bold; color: #0073aa;">
                <?php esc_html_e( 'Sei già connesso.', 'bp-magic-link' ); ?>
            </p>
        <?php endif; ?>

        <form id="bpml-form" method="POST" action="">
            <?php wp_nonce_field( 'bpml_magic_link_action', 'bpml_nonce' ); ?>
            <label for="bpml-email"><?php esc_html_e( 'Inserisci la tua email per accedere o registrarti:', 'bp-magic-link' ); ?></label><br>
            <input type="email" id="bpml-email" name="bpml_email" required style="width: 100%; max-width: 300px; padding: 8px;"><br><br>
            <input type="submit" name="bpml_submit" value="<?php esc_attr_e( 'Entra', 'bp-magic-link' ); ?>" style="padding: 8px 16px;">
        </form>
    </div>
    <?php
    
    // Gestione invio form
    if ( isset( $_POST['bpml_submit'] ) && wp_verify_nonce( $_POST['bpml_nonce'], 'bpml_magic_link_action' ) ) {
        $email = sanitize_email( wp_unslash( $_POST['bpml_email'] ) );
        if ( is_email( $email ) ) {
            bpml_process_magic_link( $email );
            echo '<p style="color: green; font-weight: bold;">' . esc_html__( "Se l'email è valida, riceverai un messaggio email con un link. Controlla la casella di posta (e lo spam), entra nel messaggio, clicca sul link e verrai indirizzato nel tuo profilo. Se accedi la prima volta dovrai completare la registrazione.", 'bp-magic-link' ) . '</p>';
        } else {
            echo '<p style="color: red;">' . esc_html__( 'Email non valida.', 'bp-magic-link' ) . '</p>';
        }
    }

    return ob_get_clean();
}

// ==========================================
// 2. GENERAZIONE E INVIO DEL MAGIC LINK
// ==========================================
function bpml_process_magic_link( $email ) {
    // Genera un token univoco di 32 caratteri
    $token = wp_generate_password( 32, false );
    
    // Salva il token nei transient di WP (scade in 15 minuti)
    set_transient( 'bpml_token_' . $token, $email, 15 * MINUTE_IN_SECONDS );

    // Costruisci il Magic Link
    $magic_link = add_query_arg( array( 'bpml_auth' => $token ), home_url() );

    // Invia l'email recuperando dinamicamente il nome del sito
    $nome_sito = get_bloginfo( 'name' );
    $subject = sprintf( __( 'Accesso al sito %s', 'bp-magic-link' ), $nome_sito );
    
    // Costruisco il messaggio traducibile passando il link come variabile
    $message_template = __( "Ciao!\n\nClicca sul link sottostante per accedere o registrarti automaticamente:\n\n%s\n\nQuesto link scadrà in 15 minuti.", 'bp-magic-link' );
    $message = sprintf( $message_template, $magic_link );
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail( $email, $subject, $message, $headers );
}

// ==========================================
// 3. INTERCETTAZIONE DEL MAGIC LINK (LOGIN O REGISTRAZIONE)
// ==========================================
add_action( 'template_redirect', 'bpml_handle_magic_link_click' );

function bpml_handle_magic_link_click() {
    if ( isset( $_GET['bpml_auth'] ) ) {
        $token = sanitize_text_field( wp_unslash( $_GET['bpml_auth'] ) );
        $email = get_transient( 'bpml_token_' . $token );

        if ( false === $email ) {
            wp_die( esc_html__( 'Link non valido o scaduto. Riprova.', 'bp-magic-link' ) );
        }

        // Token valido: cancellalo per evitare riutilizzi
        delete_transient( 'bpml_token_' . $token );

        $user = get_user_by( 'email', $email );

        if ( $user ) {
            // L'utente esiste: LOGIN
            bpml_login_user( $user );
            $redirect_url = function_exists('bp_core_get_user_domain') ? bp_core_get_user_domain( $user->ID ) : home_url(); 
            wp_redirect( $redirect_url );
            exit;
        } else {
            // L'utente non esiste: REGISTRAZIONE
            $username = wp_generate_password( 12, false ); // Username alfanumerico
            $password = wp_generate_password( 24, true );  // Password forte casuale
            
            $user_id = wp_create_user( $username, $password, $email );
            
            if ( is_wp_error( $user_id ) ) {
                wp_die( esc_html__( 'Errore durante la registrazione: ', 'bp-magic-link' ) . $user_id->get_error_message() );
            }

            // Aggiungi flag "profilo incompleto" per il cron job
            update_user_meta( $user_id, '_bpml_incomplete_profile', current_time('timestamp') );

            $new_user = get_user_by( '
