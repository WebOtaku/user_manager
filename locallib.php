<?php
defined('MOODLE_INTERNAL') || die();

define('STD_FIELDS_EN', array(
    'id', 'username', 'email',
    'city', 'country', 'lang', 'timezone', 'mailformat',
    'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
    'institution', 'department', 'idnumber', 'skype',
    'msn', 'aim', 'yahoo', 'icq', 'phone1', 'phone2', 'address',
    'url', 'description', 'descriptionformat', 'password',
    'auth',        // watch out when changing auth type or using external auth plugins!
    'oldusername', // use when renaming users - this is the original username
    'suspended',   // 1 means suspend user account, 0 means activate user account, nothing means keep as is for existing users
    'theme',       // Define a theme for user when 'allowuserthemes' is enabled.
    'deleted',     // 1 means delete user
    'mnethostid',  // Can not be used for adding, updating or deleting of users - only for enrolments, groups, cohorts and suspending.
    'interests', 'firstnamephonetic', 'lastnamephonetic', 'middlename',
    'alternatename', 'firstname', 'lastname'
));

define('STD_FIELDS_RU', array(
    'ид', 'логин', 'электронная почта', 'город', 'страна',
    'язык', 'часовой пояс', 'формат электронной почты', 'отображение электронной почты',
    'дайджест электронной почты', 'html-редактор', 'автоматическая подписка',
    'организация', 'отдел', 'идентификатор', 'skype', 'msn', 'aim', 'yahoo', 'icq',
    'телефон 1', 'телефон 2', 'адрес', 'url-адрес', 'описание', 'формат описания',
    'пароль', 'аутентификация', 'старое имя пользователя', 'заблокированный',
    'тема', 'удаленный', 'идентификатор хоста mnet', 'интересы', 'имя фонетическое',
    'фамилия фонетическая', 'отчество', 'aльтернативное имя', 'имя', 'фамилия'
));

/*define('FACULTIES', array(
    'Аграрно-технологический институт',
    'Институт национальной культуры и межкультурной коммуникации',
    'Институт экономики, управления и финансов',
    'Историко-филологический факультет',
    'Факультет иностранных языков',
    'Физико-математический факультет',
    'Факультет физической культуры, спорта и туризма',
    'Электроэнергетический факультет',
    'Юридический факультет',
    'Медицинский факультет',
    'Факультет общего и профессионального образования',
    'Институт естественных наук и фармации',
    'Психолого-педагогический факультет',
    'Институт педагогики и психологии',
    'Институт медицины и естественных наук'
));*/

// dname = lastname + firstname + middlename
define('AD_FIELDS', array(
    'lastname', 'firstname', 'middlename', 'username', 'password', 'email', 'dname', 'faculty'
));

define('AD_FIELDS_ASSOC', array(
    'LastName', 'FirstName', 'Office', 'SamAccountName', 'Passwd', 'Email', 'DName', 'Department'
));