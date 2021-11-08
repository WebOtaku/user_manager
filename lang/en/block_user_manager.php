<?php
// Общее
$string['pluginname'] = 'User Manager';

$string['user_manager'] = 'User Manager';
$string['user_manager:addinstance'] = 'Add a new "User Manager" block';
$string['user_manager:myaddinstance'] = 'Add a new "User Manager" block';
$string['user_manager:view'] = 'View "User Manager" block';
$string['user_manager:edit'] = 'Edit "User Manager" block';

$string['users_table'] = 'User List';
$string['chts_table'] = 'Cohorts List';
$string['cht_users_table'] = 'List of cohort members';
$string['cohorts'] = 'Cohorts';
$string['users'] = 'Users';
$string['instruction'] = 'Instruction';

// Форма загрузки пользователей
$string['uploaduser'] = 'Upload users';
$string['selectupmethod'] = 'Select upload method';
$string['upfile'] = 'Upload file';
$string['selectaction'] = 'Select action';

$string['validfields'] = 'Valid fields';
$string['systemfields'] = 'System fields';
$string['associatedfields'] = 'Associated fields';
$string['customfields'] = 'Custom fields';
$string['emailrequired'] = 'Email required';
$string['prffields'] = 'Profile fields (custom fields)';
$string['faculties'] = 'Faculties';
$string['faculty'] = 'Faculty';
$string['group'] = 'Group';

$string['groupinfo'] = 'Group information';

$string['examples'] = 'Examples';
$string['examplecsv_system'] = 'An example of the structure of a .csv file (separated by \"{$a->delimiter}\") with system fields';
$string['examplecsv_assoc'] = 'An example of the structure of a .csv file (separated by \"{$a->delimiter}\") with associated fields';
$string['examplecsv_desc_assoc'] = 'The names of associated fields can be written with both <b> uppercase </b> and <b> lowercase </b> letters';

$string['exportcsv'] = 'Export in .csv format';
$string['exportcsvad'] = 'Export in .csv format (AD)';
$string['exportxls'] = 'Export in .xls (Excel) format';

$string['complete'] = 'Complete';

$string['further'] = 'Further';

$string['upfromfile'] = 'Upload from File';
$string['upfrom1c'] = 'Upload from 1C';

$string['uploadmethod'] = 'Upload method';

$string['previewheader'] = 'Preview';

$string['addupdatecohort'] = 'Add/Update cohort';

$string['eduform'] = 'Form of education';
$string['full_time'] = 'Full-time';
$string['extramural'] = 'Extramural';
$string['part_time'] = 'Part-time';

// Сообщения/ошибки "Форма загрузки пользователей"
$string['uniquefields'] = 'System fields must be unique';
$string['changessaved'] = 'Changes saved';
$string['emptyrequest'] = 'Empty request (no data)';
$string['unknown'] = 'Unknown';
$string['empty'] = 'Empty';

$string['norequiredfields'] = 'The file is missing required fields: {$a->missingfields}. Add a field(s) to the file, and if the field name in the file differs from the standard one, add the appropriate associations to the table of valid fields.';
$string['requiredfields'] = 'Required fields (including "еmail ({$a->emailhelper})", if selected): {$a->requiredfields}. These fields are required to register users in the system.';
$string['emptyfile'] = 'File contains no entries.';
$string['emptygroup'] = 'There is no information about the composition of the group.';
$string['nogroupinfo'] = 'There is no information about the group.';
$string['nofacultyspecified'] = 'Faculty must be specified.';
$string['nogroupspecified'] = 'Group must be specified.';
$string['noeduformspecified'] = 'Education form must be specified.';
$string['noiidspecified'] = 'iid must be specified (identifier of temporary file).';
$string['groupnotarray'] = 'Group cannot be an array.';

$string['inputdelimiter'] = 'Field names separated by commas';

// Cohort sync
$string['nocohortinfo'] = 'There is no information about the cohort.';
$string['cohortinfo'] = 'Cohort information';
$string['selectcohort'] = 'Select cohort';
$string['cohortnotarray'] = 'Cohort cannot be an array.';
$string['nocohortspecified'] = 'Cohort must be specified.';

// Форма добавления пользователя в группу
$string['add'] = 'Add';
$string['addtocht'] = 'Adding to cohorts';
$string['addtochtshort'] = 'Adding to cohorts';

// Удаление пользователя из глобальной группы
$string['removefromcht_header'] = 'Removing from cohort';
$string['removefromcht_alt'] = 'Remove from cohort';
$string['removefromcht_warning'] = 'Are you sure you want to delete user {$a->lastname} {$a->firstname} {$a->middlename} from cohort {$a->chtname}';

// Удаление пользователя зачисленного вручную из курса
$string['removemanualenroluser_header'] = 'Removing manual enrolled user';
$string['removemanualenroluser_alt'] = 'Remove manual enrolled user';
$string['removemanualenroluser_warning'] = 'Are you sure you want to delete manual enrolled user {$a->lastname} {$a->firstname} {$a->middlename} from course {$a->coursename}';

// Добавление глобальной группы и зачисление пользователей
$string['addcohortwithusers_header'] = 'Adding cohort and assign users';
$string['addcohortwithusers_warning'] = 'Add / update the global group {$a->cohort_name} and enroll the uploaded users in it. ATTENTION! If the global group already exists and users are enrolled in it, then all users of the global group that do not match the uploaded users will be removed from the global group';

// Поля в списке пользователей
$string['firstname'] = 'First name';
$string['lastname'] = 'Surname';
$string['firstnamephonetic'] = 'First name - phonetic';
$string['lastnamephonetic'] = 'Surname - phonetic';
$string['middlename'] = ' Middle name';
$string['alternatename'] = 'Alternate name';
$string['email'] = 'E-mail';
$string['city'] = 'City/town';
$string['country'] = 'Country';
$string['lastaccess'] = 'Last access';
$string['username'] = 'Username';

// Добавленные поля
$string['course'] = 'Course';
$string['roles'] = 'Roles';
$string['cht_code_mdl'] = 'Group code (moodle)';
$string['cht_code'] = 'Group code';
$string['description'] = 'Group description';
$string['form'] = 'Studying form';
$string['enrol_method'] = 'Enrol method';

// Таблица
$string['noentries'] = 'No entries';

// Ошибки
$string['invaliddata'] = 'Invalid data';

// Действия
$string['delete'] = 'Delete';