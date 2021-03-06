<?php

    use Librarys\App\AppAlert;
    use Librarys\App\AppDirectory;
    use Librarys\App\AppLocationPath;
    use Librarys\App\AppParameter;
    use Librarys\App\Config\AppConfig;
    use Librarys\File\FileInfo;
    use Librarys\File\FileMime;

    define('LOADED', 1);
    require_once('incfiles' . DIRECTORY_SEPARATOR . 'global.php');

    if (AppDirectory::getInstance()->isFileExistsDirectorySeparatorName() == false)
        AppAlert::danger(lng('home.alert.path_not_exists'), ALERT_INDEX, env('app.http.host'));
    else if (AppDirectory::getInstance()->isPermissionDenyPath(AppDirectory::getInstance()->getDirectory()))
        AppAlert::danger(lng('home.alert.path_not_permission', 'path', AppDirectory::getInstance()->getDirectoryAndName()), ALERT_INDEX, env('app.http.host'));

    $appLocationPath = new AppLocationPath('index.php');
    $appLocationPath->setIsPrintLastEntry(true);
    $appLocationPath->setIsLinkLastEntry(true);

    $appParameter = new AppParameter();
    $appParameter->add(AppDirectory::PARAMETER_DIRECTORY_URL, AppDirectory::getInstance()->getDirectoryAndNameEncode(), true);
    $appParameter->add(AppDirectory::PARAMETER_PAGE_URL,      AppDirectory::getInstance()->getPage(),                   AppDirectory::getInstance()->getPage() > 1);

    if (isset($_GET[AppDirectory::PARAMETER_LIST_URL]))
        $backParameter = $appParameter->toString();

    $appParameter->add(AppDirectory::PARAMETER_DIRECTORY_URL, AppDirectory::getInstance()->getDirectoryEncode(), true);
    $appParameter->add(AppDirectory::PARAMETER_NAME_URL,      AppDirectory::getInstance()->getNameEncode(),      true);

    if (isset($_GET[AppDirectory::PARAMETER_LIST_URL])) {
        $appParameter->add(AppDirectory::PARAMETER_LIST_URL, 1, true)->toString(true);
    } else {
        $backParameter = $appParameter->toString(true);
    }

    $fileInfo    = new FileInfo(AppDirectory::getInstance()->getDirectory() . SP . AppDirectory::getInstance()->getName());
    $fileMime    = new FileMime($fileInfo);
    $isDirectory = $fileInfo->isDirectory();

    if ($isDirectory)
        $title = lng('file_rename.title_page_directory');
    else
        $title = lng('file_rename.title_page_file');

    AppAlert::setID(ALERT_FILE_RENAME);
    require_once('incfiles' . SP . 'header.php');

    $forms = [
        'name'     => AppDirectory::getInstance()->getName(),
        'path_old' => null,
        'path_new' => null
    ];

    if (isset($_POST['rename'])) {
        $forms['name'] = addslashes($_POST['name']);

        if (empty($forms['name'])) {
            if ($isDirectory)
                AppAlert::danger(lng('file_rename.alert.not_input_name_directory'));
            else
                AppAlert::danger(lng('file_rename.alert.not_input_name_file'));
        } else if (FileInfo::isNameValidate($forms['name']) == false) {
            if ($isDirectory)
                AppAlert::danger(lng('file_rename.alert.name_directory_not_validate', 'validate', FileInfo::FILENAME_VALIDATE));
            else
                AppAlert::danger(lng('file_rename.alert.name_file_not_validate', 'validate', FileInfo::FILENAME_VALIDATE));
        } else if (AppDirectory::getInstance()->getName() == $forms['name']) {
            AppAlert::danger(lng('file_rename.alert.name_not_change'));
        } else {
            $forms['path_old'] = FileInfo::filterPaths(AppDirectory::getInstance()->getDirectory() . SP . AppDirectory::getInstance()->getName());
            $forms['path_new'] = FileInfo::filterPaths(AppDirectory::getInstance()->getDirectory() . SP . $forms['name']);

            if (FileInfo::rename($forms['path_old'], $forms['path_new']) == false) {
                if ($isDirectory)
                    AppAlert::danger(lng('file_rename.alert.rename_directory_failed', 'filename', AppDirectory::getInstance()->getName()));
                else
                    AppAlert::danger(lng('file_rename.alert.rename_file_failed', 'filename', AppDirectory::getInstance()->getName()));
            } else {
                $idAlert = null;
                $urlGoto = null;

                if (AppConfig::getInstance()->get('auto_redirect.file_rename', true)) {
                    $appParameter->remove(AppDirectory::PARAMETER_NAME_URL);
                    $appParameter->toString(true);

                    $idAlert = ALERT_INDEX;
                    $urlGoto = 'index.php' . $appParameter->toString();
                } else {
                    $appParameter->set(AppDirectory::PARAMETER_NAME_URL, AppDirectory::rawEncode($forms['name']), true);
                    $urlGoto = 'file_rename.php' . $appParameter->toString(true);
                }

                if ($isDirectory)
                    AppAlert::success(lng('file_rename.alert.rename_directory_success', 'filename', AppDirectory::getInstance()->getName()), $idAlert, $urlGoto);
                else
                    AppAlert::success(lng('file_rename.alert.rename_file_success', 'filename', AppDirectory::getInstance()->getName()), $idAlert, $urlGoto);
            }
        }

        $forms['name'] = stripslashes(htmlspecialchars($forms['name']));
    }
?>

    <?php AppAlert::display(); ?>
    <?php $appLocationPath->display(); ?>

    <div class="form-action">
        <div class="title">
            <?php if ($isDirectory) { ?>
                <span><?php echo lng('file_rename.title_page_directory'); ?>: <?php echo AppDirectory::getInstance()->getName(); ?></span>
            <?php } else { ?>
                <span><?php echo lng('file_rename.title_page_file'); ?>: <?php echo AppDirectory::getInstance()->getName(); ?></span>
            <?php } ?>
        </div>
        <form action="file_rename.php<?php echo $appParameter->toString(); ?>" method="post">
            <input type="hidden" name="<?php echo cfsrTokenName(); ?>" value="<?php echo cfsrTokenValue(); ?>"/>

            <ul class="form-element">
                <li class="input">
                    <?php if ($isDirectory) { ?>
                        <span><?php echo lng('file_rename.form.input.name_directory'); ?></span>
                    <?php } else { ?>
                        <span><?php echo lng('file_rename.form.input.name_file'); ?></span>
                    <?php } ?>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($forms['name']); ?>" placeholder="<?php if ($isDirectory) echo lng('file_rename.form.placeholder.input_name_directory'); else echo lng('file_rename.form.placeholder.input_name_file'); ?>"/>
                </li>
                <li class="button">
                    <button type="submit" name="rename" id="button-save-on-javascript">
                        <span><?php echo lng('file_rename.form.button.rename'); ?></span>
                    </button>
                    <a href="index.php<?php echo $backParameter; ?>">
                        <span><?php echo lng('file_rename.form.button.cancel'); ?></span>
                    </a>
                </li>
            </ul>
        </form>
    </div>

    <ul class="menu-action">
        <li>
            <a href="file_info.php<?php echo $appParameter->toString(); ?>">
                <span class="icomoon icon-about"></span>
                <span><?php echo lng('file_info.menu_action.info'); ?></span>
            </a>
        </li>
        <?php if ($fileMime->isFormatTextEdit()) { ?>
            <?php if ($fileMime->isFormatTextAsEdit()) { ?>
                <li>
                    <a href="file_edit_text.php<?php echo $appParameter->toString(); ?>">
                        <span class="icomoon icon-edit"></span>
                        <span><?php echo lng('file_info.menu_action.edit_as_text'); ?></span>
                    </a>
                </li>
            <?php } else { ?>
                <li>
                    <a href="file_edit_text.php<?php echo $appParameter->toString(); ?>">
                        <span class="icomoon icon-edit"></span>
                        <span><?php echo lng('file_info.menu_action.edit_text'); ?></span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <?php if ($isDirectory == false) { ?>
            <li>
                <a href="file_download.php<?php echo $appParameter->toString(); ?>">
                    <span class="icomoon icon-download"></span>
                    <span><?php echo lng('file_info.menu_action.download'); ?></span>
                </a>
            </li>

            <?php if ($fileMime->isFormatArchiveZip()) { ?>
                <li>
                    <a href="file_unzip.php<?php echo $appParameter->toString(); ?>">
                        <span class="icomoon icon-archive"></span>
                        <span><?php echo lng('file_info.menu_action.unzip'); ?></span>
                    </a>
                </li>
                <li>
                    <a href="file_viewzip.php<?php echo $appParameter->toString(); ?>">
                        <span class="icomoon icon-archive"></span>
                        <span><?php echo lng('file_info.menu_action.viewzip'); ?></span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <li>
            <a href="file_copy.php<?php echo $appParameter->toString(); ?>">
                <span class="icomoon icon-copy"></span>
                <span><?php echo lng('file_info.menu_action.copy'); ?></span>
            </a>
        </li>
        <li>
            <a href="file_delete.php<?php echo $appParameter->toString(); ?>">
                <span class="icomoon icon-trash"></span>
                <span><?php echo lng('file_info.menu_action.delete'); ?></span>
            </a>
        </li>
        <li>
            <a href="file_chmod.php<?php echo $appParameter->toString(); ?>">
                <span class="icomoon icon-key"></span>
                <span><?php echo lng('file_info.menu_action.chmod'); ?></span>
            </a>
        </li>
    </ul>

<?php require_once('incfiles' . SP . 'footer.php'); ?>
