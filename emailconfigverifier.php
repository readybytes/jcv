<?php

/**
* @copyright	Copyright (C) 2009 - 2013 Ready Bytes Software Labs Pvt. Ltd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @contact		team@readybytes.in
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


// check if Payplans installed or not
jimport('joomla.filesystem.file');

if(!defined('DS')){
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * System Plugin
 *
 * @package	Test Email
 * @subpackage	Plugin
 * @author 	puneetsinghal@readybytes.in
 *
 */
class  plgSystemEmailconfigverifier extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected 	$autoloadLanguage 	= true;
	public 		$_app 				= null;
	protected 	$_tplVars 			= array();

	function __construct(& $subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->_app = JFactory::getApplication();

		if(JVERSION < 3.0){
			$this->loadLanguage();
		}
	}

	function onAfterRender()
	{
		$option = JRequest::getVar('option');
		if($option != 'com_config'){
			return true;
		}
		// Only render for HTML output
		if (JFactory::getDocument()->getType() !== 'html' ) { return; }

		if(JVERSION < 3.0){
			$html = $this->_getJ25Html();
		}
		elseif (JVERSION >= 3.0){
			$html = $this->_getJ30Html();
		}
		$body = JResponse::getBody();
		$body = str_replace('</body>', $html.'</body>', $body);
		JResponse::setBody($body);

		return true;
	}

	function onAfterRoute()
	{
		$input = $this->_app->input;
		$option = $input->get('option', false);
		$is_testemail = $input->get('plg_testemail', false);

		if($option != 'com_config' || !$is_testemail){
			return true;
		}

		$from 	= $input->get('from_email', false, 'string');
		$sender = $input->get('from_name', false);
		$date 	= JDate::getInstance()->toSql();

		$subject = JText::_('PLG_SYSTEM_TESTEMAIL_EMAIL_SUBJECT');
		$body 	 = JText::sprintf('PLG_SYSTEM_TESTEMAIL_EMAIL_BODY', $date);
		$result	 = array();
		$message = JText::_('PLG_SYSTEM_TESTEMAIL_MESSAGE_SUCCESS');
		$state	 = 'message';

		$mailer = self::createMailer();

		$recipient = $this->params->get('mailto', $from);

		if ($mailer->sendMail($from, $sender, $recipient, $subject, $body) !== true)
		{
			$this->_error();
		}

		$this->_success();
	}

	protected static function createMailer()
	{
		$input = JFactory::getApplication()->input;

		$smtpauth 	= $input->get('smtp_auth', 0);
		$smtpuser 	= $input->get('smtp_user', false, 'string');
		$smtppass 	= $input->get('smtp_pass', false, 'string');
		$smtphost 	= $input->get('smtp_host', false);
		$smtpsecure = $input->get('smtp_secure', false);
		$smtpport 	= $input->get('smtp_port', false);
		$mailfrom 	= $input->get('from_email', false, 'string');
		$fromname 	= $input->get('from_name', false);
		$mailer 	= $input->get('mailer', false);

		// Create a JMail object
		$mail = JMail::getInstance();

		// Set default sender without Reply-to
		$mail->SetFrom(JMailHelper::cleanLine($mailfrom), JMailHelper::cleanLine($fromname), 0);

		// Default mailer is to use PHP's mail function
		switch ($mailer)
		{
			case 'smtp':
				$mail->useSMTP($smtpauth, $smtphost, $smtpuser, $smtppass, $smtpsecure, $smtpport);
				break;

			case 'sendmail':
				$mail->IsSendmail();
				break;

			default:
				$mail->IsMail();
				break;
		}

		return $mail;
	}

	protected function _error()
	{
		$result['status'] = 'error';
		$result['message']= JText::_('PLG_SYSTEM_TESTEMAIL_MESSAGE_ERROR');
		$result = json_encode($result);

		echo $result;
		exit();
	}

	protected function _success()
	{
		$result['status'] = 'success';
		$result['message']= JText::_('PLG_SYSTEM_TESTEMAIL_MESSAGE_SUCCESS');
		$result = json_encode($result);

		echo $result;
		exit();
	}

	private function _getJ25Html()
	{
		ob_start();
		?>

		<style type="text/css">
			.btn {
			  display: inline-block;
			  padding: 6px 12px;
			  margin-bottom: 0;
			  font-size: 14px;
			  font-weight: 500;
			  line-height: 1.428571429;
			  text-align: center;
			  white-space: nowrap;
			  vertical-align: middle;
			  cursor: pointer;
			  border: 1px solid #ccc;
			  border-radius: 4px;
			  -webkit-user-select: none;
			     -moz-user-select: none;
			      -ms-user-select: none;
			       -o-user-select: none;
			          user-select: none;
			}

			.pull-right{
				float: right;
			}
		</style>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
		<script type="text/javascript">
			(function($){
				$(document).ready(function(){
					var html = "<span class=\"pull-right center\" style=\"line-height:10px;\">";
					html = html + "<button class=\"btn\" type=\"button\" id=jxi_test_email>";
					html = html + "<i class=\"icon-wrench\"></i>&nbsp;<?php echo JText::_('PLG_SYSTEM_TESTEMAIL_EMAIL_TEXT_BUTTON') ?>";
					html = html + "</button><br/>";
					html = html + "<span style=\"font-size:9px; \">";
					html = html + "PoweredBy ";
					html = html + "<a href=\"http:\/\/www.jpayplans.com\" target=\"_blank\">Ready Bytes</a>"
					html = html + "</span>";
					html = html + "</span>";

					$("#jform_mailer-lbl").parent().append(html);
				});
			})(jQuery);
		</script>

		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$html .= $this->_getTestEmailScript();
		return $html;
	}

	private function _getJ30Html()
	{
		ob_start();
		?>
		<script type="text/javascript">
			(function($){
				$(document).ready(function(){
					var html = "<span class=\"pull-right center\" style=\"line-height:1px;\">";
						html = html + "<button class=\"btn\" type=\"button\" id=jxi_test_email>";
						html = html + "<i class=\"icon-wrench\"></i>&nbsp;<?php echo JText::_('PLG_SYSTEM_TESTEMAIL_EMAIL_TEXT_BUTTON') ?>";
						html = html + "</button><br/>";
						html = html + "<span style=\"font-size:9px; \">";
						html = html + "PoweredBy ";
						html = html + "<a href=\"http:\/\/www.jpayplans.com\" target=\"_blank\">Ready Bytes</a>"
						html = html + "</span>";
						html = html + "</span>";

					$("#jform_mailer-lbl").closest('fieldset').find('legend').append(html);
					$("#content").prepend("<div id=\"jxi_email_msg\">&nbsp;</div>");
				});
			})(jQuery);
		</script>

		<?php
		$html = ob_get_contents();
		ob_end_clean();
		$html .= $this->_getTestEmailScript();
		return $html;
	}

	private function _getTestEmailScript()
	{
		$root = JURI::root();
		ob_start();
		?>
		<script type="text/javascript">
			(function($){
				$(document).ready(function(){
					$("#jxi_test_email").click(function(){
						$('body').css('opacity','0.4');
						var smtp_auth	= 1;
						if($('#jform_smtpauth1').prop("checked")){
							smtp_auth	= 0;
						}

						var url = "<?php echo $root;?>";
						url = url + 'administrator/index.php?option=com_config&';
						url = url + 'plg_testemail=1';

						var from_email	= $("#jform_mailfrom").val();
						var mailer		= $('#jform_mailer :selected').val();
						var from_name	= $('#jform_fromname').val();
						var send_path	= $('#jform_sendmail').val();
						var smtp_secure	= $('#jform_smtpsecure :selected').val();
						var smtp_port	= $('#jform_smtpport').val();
						var smtp_user	= $('#jform_smtpuser').val();
						var smtp_pass	= $('#jform_smtppass').val();
						var smtp_host	= $('#jform_smtphost').val();

						$.post(url,
								{
									from_email 	: from_email,
									mailer		: mailer,
									from_name	: from_name,
									send_path	: send_path,
									smtp_auth	: smtp_auth,
									smtp_secure	: smtp_secure,
									smtp_port	: smtp_port,
									smtp_user	: smtp_user,
									smtp_pass	: smtp_pass,
									smtp_host	: smtp_host
								},
								function(data, status){
							var record = JSON.parse(data);
							$('body').css('opacity','1');

							if(record.status == 'success'){
								$('#jxi_email_msg').removeClass("alert alert-error");
								$('#jxi_email_msg').addClass("alert alert-success");
								var message = record.message;
								$('#jxi_email_msg').html(message);
							}

							if(record.status == 'error'){
								$('#jxi_email_msg').removeClass("alert alert-success");
								$('#jxi_email_msg').addClass("alert alert-error");
								var message = record.message;
								$('#jxi_email_msg').html(message);
							}

							alert(message);

						});
					});
				});
			})(jQuery);
		</script>

		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

}