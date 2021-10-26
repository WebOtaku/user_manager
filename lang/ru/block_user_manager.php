<?php
// Общее
$string['pluginname'] = 'Менеджер пользователей';

$string['user_manager'] = 'Менеджер пользователей';
$string['user_manager:addinstance'] = 'Добавить новый блок "Менеджер пользователей"';
$string['user_manager:myaddinstance'] = 'Добавить новый блок "Менеджер пользователей"';
$string['user_manager:view'] = 'Видеть блок "Менеджер пользователей"';
$string['user_manager:edit'] = 'Редактировать блок "Менеджер пользователей"';

$string['users_table'] = 'Список пользователей';
$string['chts_table'] = 'Список глобальных групп';
$string['cht_users_table'] = 'Список участников глобальной группы';
$string['cohorts'] = 'Глобальные группы';
$string['users'] = 'Пользователи';
$string['instruction'] = 'Инструкция';

// Форма загрузки пользователей
$string['uploaduser'] = 'Загрузка пользователей';
$string['selectupmethod'] = 'Выбор способа загрузки';
$string['upfile'] = 'Загрузка файла';
$string['selectaction'] = 'Выбор действия';

$string['validfields'] = 'Допустимые поля';
$string['systemfields'] = 'Системные поля';
$string['associatedfields'] = 'Ассоциированные поля';
$string['customfields'] = 'Пользовательские поля';
$string['emailrequired'] = 'Электронная почта обязательна';
$string['prffields'] = 'Поля профиля (настраиваемые поля)';
$string['faculties'] = 'Факультеты';
$string['faculty'] = 'Факультет';
$string['group'] = 'Группа';

$string['groupinfo'] = 'Информация о группе';

$string['exportcsv'] = 'Экспорт в формате .csv';
$string['exportcsvad'] = 'Экспорт в формате .csv (AD)';
$string['exportxls'] = 'Экспорт в формате .xls (Excel)';

$string['complete'] = 'Выполнить';

$string['further'] = 'Далее';

$string['upfromfile'] = 'Загрузка из Файла';
$string['upfrom1c'] = 'Загрузка из 1С';

$string['uploadmethod'] = 'Способ загрузки';

$string['previewheader'] = 'Предпросмотр';

$string['addupdatecohort'] = 'Добавить/Обновить глобальную группу';

$string['eduform'] = 'Форма обучения';
$string['full_time'] = 'Очная';
$string['extramural'] = 'Заочная';
$string['part_time'] = 'Очно-заочная';

// Сообщения/ошибки "Форма загрузки пользователей"
$string['uniquefields'] = 'Системные поля должны быть уникальными';
$string['changessaved'] = 'Изменения сохранены';
$string['emptyrequest'] = 'Пустой запрос (данных нет)';
$string['unknown'] = 'Неизвестно';
$string['empty'] = 'Пусто';

$string['norequiredfields'] = 'В файле отсутствуют необходимые поля: {$a->missingfields}. Добавьте поле(-я) в файл и в случае если название поля в файле отличается от стандартного внесите соотвествующие ассоциации в таблицу допустимых полей.';
$string['requiredfields'] = 'Обязательные поля (включая "еmail ({$a->emailhelper})", если выбрано): {$a->requiredfields}. Данные поля необходимы для регистрации пользователей в системе.';
$string['emptyfile'] = 'Файл не содержит записей.';
$string['emptygroup'] = 'Отсутствует информация о составе группы.';
$string['nogroupinfo'] = 'Отсутствует информация о группе.';
$string['nofacultyspecified'] = 'Должен быть указан факультет.';
$string['nogroupspecified'] = 'Должна быть указана группа.';
$string['noeduformspecified'] = 'Должна быть указана форма обучения.';
$string['noiidspecified'] = 'Должен быть указана iid (идентификатор временного файла).';
$string['groupnotarray'] = 'Группа не может быть массивом.';

$string['inputdelimiter'] = 'Названия полей разделены запятой';

// Форма добавления пользователя в группу
$string['add'] = 'Добавить';
$string['addtocht'] = 'Добавление в глобальные группы';
$string['addtochtshort'] = 'Доб. в глоб. группы';

// Удаление пользователя из глобальной группы
$string['removefromcht_header'] = 'Удаление из глобальной группы';
$string['removefromcht_alt'] = 'Удалить из глобальной группы';
$string['removefromcht_warning'] = 'Вы действительно хотите удалить пользователя {$a->lastname} {$a->firstname} {$a->middlename} из глобальной группы {$a->chtname}';

// Удаление пользователя зачисленного вручную из курса
$string['removemanualenroluser_header'] = 'Удаление зачисленного вручную пользователя';
$string['removemanualenroluser_alt'] = 'Удалить зачисленного вручную пользователя';
$string['removemanualenroluser_warning'] = 'Вы действительно хотите удалить зачисленного вручную пользователя {$a->lastname} {$a->firstname} {$a->middlename} из курса {$a->coursename}';

// Добавление глобальной группы и зачисление пользователей
$string['addcohortwithusers_header'] = 'Добавление глобальной группы и зачисление пользователей';
$string['addcohortwithusers_warning'] = 'Добавить/обновить глобальную группу {$a->cohort_name} и зачислить в неё загруженных пользователей. ВНИМАНИЕ! В случае если глобальная группа уже существует и в неё зачислены пользователи, то все пользователи глобальной группы не совпадающие с загруженными пользователями будут удалены из глобальной группы.';

// Поля в списке пользователей
$string['firstname'] = 'Имя';
$string['lastname'] = 'Фамилия';
$string['firstnamephonetic'] = 'Имя - фонетическая запись';
$string['lastnamephonetic'] = 'Фамилия - фонетическая запись';
$string['middlename'] = 'Отчество';
$string['alternatename'] = 'Альтернативное имя';
$string['email'] = 'E-mail';
$string['city'] = 'Город';
$string['country'] = 'Страна';
$string['lastaccess'] = 'Последний вход';
$string['username'] = 'Логин';

// Добавленные поля
$string['course'] = 'Курс';
$string['roles'] = 'Роли';
$string['cht_code_mdl'] = 'Код группы (мудл)';
$string['cht_code'] = 'Код группы';
$string['description'] = 'Описание группы';
$string['form'] = 'Форма обучения';
$string['enrol_method'] = 'Способ записи';

// Таблица
$string['noentries'] = 'Записи отсутствуют';

// Ошибки
$string['invaliddata'] = 'Неверные данные';

// Действия
$string['delete'] = 'Удалить';