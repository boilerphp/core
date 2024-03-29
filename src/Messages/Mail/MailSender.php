<?php

namespace Boiler\Core\Messages\Mail;

use Boiler\Core\Configs\GlobalConfig;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use ErrorException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class MailSender extends MailBuilder
{

    protected $mail;
    protected $mailer;

    protected $driver;
    protected $response;
    protected $transport;

    protected $smtpHost;
    protected $smtpPort;
    protected $smtpDebug;
    protected $smtpUsername;
    protected $smtpPassword;
    protected $smtpEncryption;


    public function getMailAttributes()
    {
        $app_config = GlobalConfig::getAppConfigs();

        $this->driver = $app_config->mailAttributes->driver;

        $this->checkMailAttributes(
            ["smtpHost", "smtpUsername", "smtpPassword", "smtpPort"],
            $app_config->mailAttributes->smtp
        );

        $this->smtpDebug = $app_config->mailAttributes->smtp->smtpDebug;
        $this->smtpHost = $app_config->mailAttributes->smtp->smtpHost;
        $this->smtpUsername = $app_config->mailAttributes->smtp->smtpUsername;
        $this->smtpPassword = $app_config->mailAttributes->smtp->smtpPassword;
        $this->smtpPort = $app_config->mailAttributes->smtp->smtpPort;
        $this->smtpEncryption = $app_config->mailAttributes->smtp->smtpEncryption;
    }

    public function checkMailAttributes(array $variables, object $mailAttributes)
    {
        foreach ($variables as $variable) {
            if (!isset($mailAttributes->$variable)) {
                throw new ErrorException($variable . " not found in mail attribites of appsettings.json configurations.");
            }
        }
    }


    public function createTransport()
    {
        return $this->transport = (new Swift_SmtpTransport($this->smtpHost, $this->smtpPort))
            ->setUsername($this->smtpUsername)
            ->setPassword($this->smtpPassword);
    }


    public function createMailer()
    {
        // Create the Mailer using your created Transport
        return $this->mailer = new Swift_Mailer($this->transport);
    }

    public function buildMessage()
    {
        // Create a message
        $this->mail = (new Swift_Message($this->subject))
            ->setFrom(array($this->from => $this->fromName))
            ->setTo(array($this->to => $this->toName))
            ->setContentType($this->contentType)
            ->setCharset($this->charset)
            ->setBody($this->message);

        return $this->mail;
    }

    public function PHPMailer()
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = $this->smtpDebug;                // Enable verbose debug output
            $mail->isSMTP();                                         // Send using SMTP
            $mail->Host       = $this->smtpHost;                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                      // Enable SMTP authentication
            $mail->Username   = $this->smtpUsername;                    // SMTP username
            $mail->Password   = $this->smtpPassword;                    // SMTP password
            $mail->SMTPSecure = $this->smtpEncryption; //PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port       = $this->smtpPort;                         // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($this->to, $this->toName);     // Add a recipient
            $mail->addReplyTo($this->replyTo, $this->replyName);

            if (count($this->ccs)) {
                foreach ($this->ccs as $address) {
                    $mail->addCC($address["email"], $address["name"]);
                }
            }

            if (count($this->bccs)) {
                foreach ($this->bccs as $address) {
                    $mail->addBCC($address["email"], $address["name"]);
                }
            }

            // Attachments
            $attachments = $this->getAttachments();
            if (count($attachments)) {
                foreach ($attachments as $attachment) {
                    $mail->addAttachment($attachment);         // Add attachments
                }
            }

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $this->subject;
            $mail->Body    = $this->message;
            // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            $this->response = 'sent';
            return true;
        } catch (Exception $e) {
            $this->response = "Failed: {$mail->ErrorInfo}";
            return false;
        }
    }

    public function defaultMailer()
    {
        $headers = "MIME-VERSION: $this->mime" . " \r\n";
        $headers .= "Content-type: $this->contentType; charset=$this->charset " . "\r\n";
        $headers .= "From: $this->fromName $this->from \r\n";
        $headers .= 'Reply-To: ' . $this->replyTo . "\r\n";

        if (mail($this->to, $this->subject, $this->message, $headers)) {
            return true;
        }

        return false;
    }

    public function sendMail()
    {
        // Send the message
        $this->getMailAttributes();

        if ($this->driver == "swiftmailer") {
            if ($this->createTransport()) {
                $this->createMailer();
                return $this->mailer->send($this->buildMessage());
            }
        } else if ($this->driver == "phpmailer") {
            return $this->PHPMailer();
        } else if ($this->driver == "default") {
            return $this->defaultMailer();
        }

        return false;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
