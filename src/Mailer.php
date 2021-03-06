<?php

namespace Crunz;

use Crunz\Configuration\Configurable;

class Mailer extends Singleton {

    use Configurable;

    /**
     * Mailer instance
     *
     * @param \Swift_Mailer
     */
    protected $mailer;

    /**
     * Instantiate the Mailer class
     *
     * @param \Swift_Mailer $mailer
     */
    public function __construct(\Swift_Mailer $mailer = null)
    {
        $this->configurable();
        $this->mailer = $mailer;
    }

    /**
     * Return the proper mailer
     *
     * @return \Swift_Mailer
     */
    protected function getMailer()
    {
        // If the mailer has already been defined via the constructor, return it.
        if ($this->mailer) {
            return $this->mailer;
        }

        // Get the proper transporter
        switch ($this->config('mailer.transport')) {           
            case 'smtp':
            $transport = $this->getSmtpTransport();
            break;

            case 'mail':
            $transport = $this->getMailTranport();
            break;
            
            default:
            $transport = $this->getSendMailTransport();
        }

        return method_exists(\Swift_Mailer::class, 'newInstance')
            ? \Swift_Mailer::newInstance($transport)
            : new \Swift_Mailer($transport);
    }

    /**
     * Get the SMTP transport
     *
     * @return \Swift_SmtpTransport
     */
    protected function getSmtpTransport()
    {
        $object = method_exists(\Swift_SmtpTransport::class, 'newInstance')
            ? \Swift_SmtpTransport::newInstance(
                $this->config('smtp.host'),
                $this->config('smtp.port'),
                $this->config('smtp.encryption')
            )
            : new \Swift_SmtpTransport(
                $this->config('smtp.host'),
                $this->config('smtp.port'),
                $this->config('smtp.encryption')
            );

      return $object
        ->setUsername($this->config('smtp.username'))
        ->setPassword($this->config('smtp.password'));
    }

    /**
     * Get the Mail transport
     *
     * @return \Swift_MailTransport
     */
    protected function getMailTrasport()
    {
        if (!class_exists('\Swift_MailTransport')) {
            throw new \Exception('Mail transport has been removed in SwiftMailer 6');
        }
        return \Swift_MailTransport::newInstance();
    }

    /**
     * Get the Sendmail Transport
     *
     * @return \Swift_SendmailTransport
     */
    protected function getSendMailTransport()
    {
        return method_exists(\Swift_SendmailTransport::class, 'newInstance')
            ? \Swift_SendmailTransport::newInstance()
            : new \Swift_SendmailTransport();
    }

    /**
     * Send an email
     *
     * @param  string $subject
     * @param  string $message
     * 
     * @return boolean 
     */
    public function send($subject, $message)
    {
        $this->getMailer()->send($this->getMessage($subject, $message));
    }

    /**
     * Prepare a swift message object
     *
     * @param  string $subject
     * @param  string $message
     * 
     * @return \Swift_Message
     *
     */
    protected function getMessage($subject, $message)
    {
        $object = method_exists(\Swift_Message::class, 'newInstance')
            ? \Swift_Message::newInstance()
            : new \Swift_Message();

        return  $object
                 ->setBody($message)
                 ->setSubject($subject)
                 ->setFrom([$this->config('mailer.sender_email') => $this->config('mailer.sender_name')])
                 ->setTo($this->config('mailer.recipients'));
    }

}