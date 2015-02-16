<?php if (!defined('APPLICATION')) exit(); ?>
<script language="javascript">
   jQuery(document).ready(function($) {
      $('#Form_ReasonText').focus(function() {
         $('#Form_Reason2').attr('checked', 'checked');
      });
   });
</script>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="Warning">
   <?php
   echo FormatString(T('You are about to ban {User.UserID,user}.'), $this->Data);
   ?>
</div>

<?php
echo '<div class="TempBanPeriodWrap">',
    '<div class="P"><b>'. T('Temporary ban duration') .'</b></div>',
    '<div class="P">',
    '<span>'.T('Minutes').'</span>'.$this->Form->Dropdown('TempBanPeriodMinutes', range(0,60)),
    '<span>'.T('Hours').'</span>'.$this->Form->Dropdown('TempBanPeriodHours', range(0,24)),
    '<span>'.T('Days').'</span>'.$this->Form->Dropdown('TempBanPeriodDays', range(0,31)),
    '<span>'.T('Months').'</span>'.$this->Form->Dropdown('TempBanPeriodMonths', range(0,12)),
    '<span>'.T('Years').'</span>'.$this->Form->Dropdown('TempBanPeriodYears', range(0,10)),
    '</div>',
    '</div>';
?>

<div class="P"><b><?php echo T('Why are you Banning this user?') ?></b></div>

<?php
echo '<div class="P">', $this->Form->Radio('Reason', 'Spamming', array('Value' => 'Spam')), '</div>';
echo '<div class="P">', $this->Form->Radio('Reason', 'Abusive Behavior', array('Value' => 'Abuse')), '</div>';
echo '<div class="P">', 
   $this->Form->Radio('Reason', 'Other', array('Value' => 'Other')),
   '<div class="TextBoxWrapper">',
   $this->Form->TextBox('ReasonText', array('MultiLine' => TRUE)),
   '</div>',
   '</div>';
   
echo '<div class="Buttons P">', $this->Form->Button(T('Ban.Action', 'Ban')), '</div>';
echo $this->Form->Close();
?>
</div>
