<?php
session_start(); // this MUST be called prior to any output including whitespaces and line breaks!

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Securimage Example Form</title>
  <style type="text/css">
  <!--
  .error { color: #f00; font-weight: bold; font-size: 1.2em; }
  .success { color: #00f; font-weight; bold; font-size: 1.2em; }
  fieldset { width: 90%; }
  legend { font-size: 24px; }
  .note { font-size: 18px;
  -->
  </style>
</head>
<body>

<fieldset>
<legend>Example Form</legend>

<p class="note">
  Use the form below to ask a question, send a comment, get coding help, or any other support.<br />
  Please provide as much detail as possible when asking for help with installing or using Securimge/
</p>

<?php

$GLOBALS['ct_recipient']   = 'YOU@YOUR-EMAIL';
$GLOBALS['ct_msg_subject'] = 'Securimage Test Contact Form';

//$GLOBALS['contact_subjects'] = array('Installation or Configuration Question', 'General Comment', 'Bug Report', 'Installation Support', 'Other');

process_si_contact_form();

if ($_SESSION['ctform']['error'] == true): /* The last form submission had 1 or more errors */ ?>
<span class="error">There was a problem with your submission.  Errors are displayed below in red.</span><br /><br />
<?php elseif ($_SESSION['ctform']['success'] == true): /* form was processed successfully */ ?>
<span class="success">Your message has been sent.  We will do our best to reply as quickly as possible.</span><br /><br />
<?php endif; ?>

<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING'] ?>" id="contact_form">
  <input type="hidden" name="do" value="contact" />

  <p>
    <strong>Name*:</strong>&nbsp; &nbsp;<?php echo $_SESSION['ctform']['name_error'] ?><br />
    <input type="text" name="ct_name" size="35" value="<?php echo htmlspecialchars(@$_SESSION['ctform']['ct_name']) ?>" />
  </p>

  <p>
    <strong>Email*:</strong>&nbsp; &nbsp;<?php echo $_SESSION['ctform']['email_error'] ?><br />
    <input type="text" name="ct_email" size="35" value="<?php echo htmlspecialchars($_SESSION['ctform']['ct_email']) ?>" />
  </p>

  <p>
    <strong>URL:</strong>&nbsp; &nbsp;<?php echo $_SESSION['ctform']['URL_error'] ?><br />
    <input type="text" name="ct_URL" size="35" value="<?php echo htmlspecialchars(@$_SESSION['ctform']['ct_URL']) ?>" />
  </p>

<?php /*
  <p>
    <strong>Subject*:</strong>&nbsp; &nbsp;<?php echo $_SESSION['ctform']['subject_error'] ?><br />
    <select name="ct_subject"><option value="">-- Select Subject -- </option><?php foreach($GLOBALS['contact_subjects'] as $subject): ?><option<?php if ($subject == $_SESSION['ctform']['ct_subject']): ?> selected="selected"<?php endif ?>><?php echo $subject ?></option><?php endforeach; ?></select>
  </p>
  */ ?>

  <p>
    <strong>Message*:</strong>&nbsp; &nbsp;<?php echo $_SESSION['ctform']['message_error'] ?><br />
    <textarea name="ct_message" style="width: 450px; height: 200px"><?php echo htmlspecialchars($_SESSION['ctform']['ct_message']) ?></textarea>
  </p>

  <p>
    <img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="./securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left">
    <object type="application/x-shockwave-flash" data="./securimage_play.swf?audio=./securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000" height="19" width="19">
    <param name="movie" value="./securimage_play.swf?audio=./securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000">
    </object><br />
    <a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById('siimage').src = './securimage_show.php?sid=' + Math.random(); return false"><img src="./images/refresh.gif" alt="Reload Image" onclick="this.blur()" align="bottom" border="0"></a><br />
    <strong>Security Code*:</strong><br />
     <?php echo $_SESSION['ctform']['captcha_error'] ?>
    <input type="text" name="ct_captcha" size="10" maxlength="8" />
  </p>

  <p>
    <br />
    <input type="submit" value="Submit Message">
  </p>

</form>
</fieldset>

</body>
</html>


<?php

function process_si_contact_form()
{
  global $contact_subjects;
  $_SESSION['ctform'] = array();

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && @$_POST['do'] == 'contact') {
    foreach($_POST as $key => $value) {
      if (!is_array($key)) {
        if ($key != 'ct_message') $value = strip_tags($value);
        $_POST[$key] = htmlspecialchars(stripslashes(trim($value)));
      }
    }

    $name    = @$_POST['ct_name'];
    $email   = @$_POST['ct_email'];
    $URL     = @$_POST['ct_URL'];
    $subject = @$_POST['ct_subject'];
    $message = @$_POST['ct_message'];
    $captcha = @$_POST['ct_captcha'];
    $name    = substr($name, 0, 64);

    $errors = array();

    if (strlen($name) < 3) {
      $errors['name_error'] = 'Your name is required';
    }

    if (strlen($email) == 0) {
      $errors['email_error'] = 'Email address is required';
    } else if ( !preg_match('/^(?:[\w\d]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/i', $email)) {
      $errors['email_error'] = 'Email address entered is invalid';
    }

    /*
    if ($subject == '' || !in_array($subject, $contact_subjects)) {
      $errors['subject_error'] = 'Please select a subject';
    }
    */

    if (strlen($message) < 20) {
      $errors['message_error'] = 'Please enter a message';
    }

    if (sizeof($errors) == 0) {
      require_once 'securimage.php';
      $securimage = new Securimage();
      if ($securimage->check($captcha) == false) {
        $errors['captcha_error'] = 'Incorrect security code entered<br />';
      }
    }

    if (sizeof($errors) == 0) {
      // process
      $time       = date('r');
      $message = "A message was submitted from the Securimage contact form.  The following information was provided.<br /><br />"
                    . "Name: $name<br />"
                    . "Email: $email<br />"
                    . "URL: $URL<br />"
                    . "Subject: $subject<br /><br />"
                    . "Message:<br />"
                    . "<pre>$message</pre>"
                    . "<br /><br />IP Address: {$_SERVER['REMOTE_ADDR']} ({$_SERVER['REMOTE_HOST']})<br />"
                    . "Time: $time<br />"
                    . "Browser: {$_SERVER['HTTP_USER_AGENT']}<br />";

      $message = wordwrap($message, 70);

      /* REMOVE // from next line to actually send message! */
      //mail($GLOBALS['ct_recipient'], $GLOBALS['ct_msg_subject'], $message, "From: {$GLOBALS['ct_recipient']}\r\nReply-To: {$email}\r\nContent-type: text/html; charset=ISO-8859-1\r\nMIME-Version: 1.0");

      $_SESSION['ctform']['error'] = false;
      $_SESSION['ctform']['success'] = true;
    } else {
      $_SESSION['ctform']['ct_name'] = $name;
      $_SESSION['ctform']['ct_email'] = $email;
      $_SESSION['ctform']['ct_URL'] = $URL;
      $_SESSION['ctform']['ct_subject'] = $subject;
      $_SESSION['ctform']['ct_message'] = $message;

      foreach($errors as $key => $error) {
        $_SESSION['ctform'][$key] = "<span style=\"font-weight: bold; color: #f00\">$error</span>";
      }

      $_SESSION['ctform']['error'] = true;
    }
  } // POST
}

$_SESSION['ctform']['success'] = false;
