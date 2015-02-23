<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['TempBan'] = array(
   'Name' => 'Temp Ban',
   'Description' => 'Allows an admin/moderator to ban someone for a fixed time, after which the ban will expire.',
   'Version' => '0.1.1b',
   'Author' => "Paul Thomas",
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'AuthorEmail' => 'dt01pqt_pt@yahoo.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/x00'
);

class TempBan extends Gdn_Plugin {

    public function ProfileController_BeforeProfileOptions_Handler($Sender){
        if(Gdn::Session()->CheckPermission('Garden.Moderation.Manage')){
            $Sender->EventArguments['ProfileOptions'][] = array(
                'Text' => Sprite('SpBan').' '.( !$Sender->User->Banned ? T('Temporary Ban'): T('Temporary Unban')),
                'Url' => '/user/tempban/' . intval($Sender->User->UserID) . '/' . intval($Sender->User->Banned),
                'CssClass' => 'Popup'
            );
        }
    }

    public function UserController_TempBan_Create($Sender, $Args) {
        $Sender->Permission('Garden.Moderation.Manage');

        $UserID = (int) GetValue('0',$Args);
        $Unban = (bool) GetValue('1',$Args);

        $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
        
        if (!$User) {
            throw NotFoundException($User);
        }

        $UserModel = Gdn::UserModel();

        if ($Sender->Form->AuthenticatedPostBack()) {
            if ($Unban) {
                $UserModel->Unban($UserID, array('RestoreContent' => $Sender->Form->GetFormValue('RestoreContent')));
            } else {
                $Minutes = $Sender->Form->GetValue('TempBanPeriodMinutes');
                $Hours   = $Sender->Form->GetValue('TempBanPeriodHours');
                $Days    = $Sender->Form->GetValue('TempBanPeriodDays');
                $Months  = $Sender->Form->GetValue('TempBanPeriodMonths');
                $Years   = $Sender->Form->GetValue('TempBanPeriodYears');

                if(!(empty($Minutes) && empty($Hours) && empty($Days) && empty($Months) && empty($Years))){
                    $AutoExpirePeriod = Gdn_Format::ToDateTime(strtotime("+{$Years} years {$Months} months {$Days} days {$Hours} hours {$Minutes} minutes"));
                }else{
                    $Sender->Form->AddError('ValidateRequired', 'Ban Period');
                }

                if (!ValidateRequired($Sender->Form->GetFormValue('Reason'))) {
                   $Sender->Form->AddError('ValidateRequired', 'Reason');
                }
                if ($Sender->Form->GetFormValue('Reason') == 'Other' && !ValidateRequired($Sender->Form->GetFormValue('ReasonText'))) {
                   $Sender->Form->AddError('ValidateRequired', 'Reason Text');
                }

                if ($Sender->Form->ErrorCount() == 0) {
                    if ($Sender->Form->GetFormValue('Reason') == 'Other') {
                        $Reason = $Sender->Form->GetFormValue('ReasonText');
                    } else {
                        $Reason = $Sender->Form->GetFormValue('Reason');
                    }

                    Gdn::Locale()->SetTranslation('HeadlineFormat.Ban', FormatString('{RegardingUserID,You} banned {ActivityUserID,you} until {BanExpire, date}.', array('BanExpire' => $AutoExpirePeriod)));
                    $UserModel->Ban($UserID, array('Reason' => $Reason));
                    $UserModel->SetField($UserID, 'BanExpire', $AutoExpirePeriod);
                }
            }

            if ($Sender->Form->ErrorCount() == 0) {
                // Redirect after a successful save.
                if ($Sender->Request->Get('Target')) {
                   $Sender->RedirectUrl = $Sender->Request->Get('Target');
                } else {
                   $Sender->RedirectUrl = Url(UserUrl($User));
                }
            }
        }

        $Sender->SetData('User', $User);
        $Sender->AddSideMenu();
        $Sender->Title($Unban ? T('Unban User') : T('Temporary Ban User'));
        if ($Unban)
            $Sender->View = 'Unban';
        else
            $Sender->View = $this->ThemeView('tempban');
        $Sender->Render();

    }

    public function Expire(){
        $ExpireBans = Gdn::SQL()
            ->Select('Count(UserID)')
            ->From('User')
            ->Where(
                array(
                    'Banned'=>TRUE,
                    'BanExpire<'=>Gdn_Format::ToDateTime()
                )
            )
            ->Get()
            ->Result();

        if($ExpireBans){
            Gdn::SQL()->Put(
              'User',
              array('Banned'=>FALSE,'BanExpire'=>NULL),
              array('Banned'=>TRUE,'BanExpire<'=>Gdn_Format::ToDateTime())
            );
        }
    }

    public function UserController_TempBanStatus_Create($Sender, $Args){
        $Sender->Permission('Garden.Users.Edit');
        $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
        $Sender->DeliveryType(DELIVERY_TYPE_VIEW);

        $UserID = intval(GetValue('0',$Args));

        $User = Gdn::UserModel()->GetID($UserID);

        if($User->BanExpire){
            $BanExpires =  FormatString(T('Ban Expires: {ExpiresOn}'),array('ExpiresOn' => Gdn_Format::Date($User->BanExpire)));
            $Sender->SetJson('TempBanExpires', $BanExpires);
        }

        $Sender->Render('TempBanStatus', '', 'plugins/TempBan');
    }

    public function UserController_Render_Before($Sender){
        $Sender->AddJsFile($this->GetResource('js/tempban.js', FALSE, FALSE));
    }

    public function UserModel_AfterSave_Handler($Sender,&$Args){
        $Fields = $Args['Fields'];
        $UserID = $Args['UserID'];
        // if not banned there should be no expiry
        if(!GetValue('Banned', $Fields)){
           $Sender->SetField($UserID, 'BanExpire', NULL);
        }
    }

    public function ThemeView($View){
        $ThemeViewLoc = CombinePaths(array(
            PATH_THEMES, Gdn::Controller()->Theme, 'views', $this->GetPluginFolder()
        ));

        if(file_exists($ThemeViewLoc.DS.$View.'.php')){
            $View=$ThemeViewLoc.DS.$View.'.php';
        }else{
            $View=$this->GetView($View.'.php');
        }

        return $View;
    }

    public function Base_BeforeDispatch_Handler($Sender){
        if(C('Plugins.TempBan.Version')!=$this->PluginInfo['Version'])
            $this->Structure();

        $this->Expire();
    }

    public function Setup(){
        $this->Structure();
    }

    public function Structure(){
        Gdn::Structure()
        ->Table('User')
        ->Column('BanExpire','datetime',null)
        ->Set();

        SaveToConfig('Plugins.TempBan.Version', $this->PluginInfo['Version']);
    }
}
