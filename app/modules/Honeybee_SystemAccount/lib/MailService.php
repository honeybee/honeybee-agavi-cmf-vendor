<?php

namespace Honeybee\SystemAccount;

use AgaviConfig;
use AgaviContext;
use Exception;
use Honeybee\SystemAccount\User\Projection\Standard\User;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Logging\Logger;
use Honeygavi\Renderer\ModuleTemplateRenderer;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeygavi\Mail\MailServiceInterface;
use Psr\Log\LoggerInterface;

class MailService
{
    protected $config;
    protected $mail_service;
    protected $module_template_renderer;
    protected $logger;

    public function __construct(
        ConfigInterface $config,
        MailServiceInterface $mail_service,
        ModuleTemplateRenderer $module_template_renderer,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->mail_service = $mail_service;
        $this->module_template_renderer = clone($module_template_renderer);
        $this->module_template_renderer->setConfig(['module_name' => 'Honeybee_SystemAccount']);
        $this->logger = $logger;
    }

    public function sendUserPasswordResetEmail($auth_token, User $user)
    {
        $this->logger->debug('Trying to send user password reset email to: ' . $user->getEmail());

        $tm = AgaviContext::getInstance()->getTranslationManager();
        $lm = AgaviContext::getInstance()->getLoggerManager();
        $ro = AgaviContext::getInstance()->getRouting();

        $current_language = $tm->getCurrentLocaleIdentifier();
        $user_language = $user->getLanguage();
        if (empty($user_language)) {
            $user_language = $current_language;
        }

        // TODO use locale of user instead of language of the user resource?
        $tm->setLocale($user_language);

        // this creates a bin/cli "url" when called in CLI, use gen_options_presets in factories.xml?
        //$user_password_link = $ro->gen('user.password', [ 'token' => $auth_token ]);
        // that's why this needs to be here for the moment:
        $user_password_link = sprintf(
            '%shoneybee-system_account-user/password?token=%s',
            AgaviConfig::get('local.base_href'),
            $auth_token
        );

        $message = $this->module_template_renderer->createMessageFromTemplate(
            'mails/ResetPassword',
            [
                'user_password_link' => $user_password_link,
                'user' => $user
            ]
        );

        $message->setFrom([ $this->config->get('from_email') => $this->config->get('from_name', '') ]);
        $message->setTo([ $user->getEmail() => $user->getFirstname() . ' ' . $user->getLastname() ]);

        if ($this->config->has('reply_email')) {
            $message->setReplyTo([ $this->config->get('reply_email') => $this->config->get('reply_name', '') ]);
        }

        // sender email can be used as theoretically multiple from addresses may be used for emails
        if ($this->config->has('sender_email')) {
            $message->setSender([ $this->config->get('sender_email') => $this->config->get('sender_name', '') ]);
        }

        try {
            $info = $this->mail_service->send($message);
        } catch (Exception $e) {
            $lm->logTo(
                'mail',
                Logger::ERROR,
                __METHOD__,
                [
                    'Unable to send ResetPassword email to',
                    $user->getEmail(),
                    '- exception was:',
                    $e,
                    'Mail is:',
                    $message
                ]
            );
            $tm->setLocale($current_language);

            throw new RuntimeError(
                'Unable to send email. Please try again later or contact the responsible staff.'
            );
        }

        if (count($info[MailServiceInterface::FAILED_RECIPIENTS]) !== 0) {
            $lm->logTo(
                'mail',
                Logger::ERROR,
                __METHOD__,
                [
                    'Failed to send ResetPassword email to',
                    $user->getEmail(),
                    '- return value was:', $info
                ]
            );
            $tm->setLocale($current_language);

            throw new RuntimeError(
                'Unable to deliver email correctly. Please try again later or contact the responsible staff.'
            );
        }

        $tm->setLocale($current_language);

        $this->logger->debug('Password reset mail sent to: ' . $user->getEmail());
    }
}
