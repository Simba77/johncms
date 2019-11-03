<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Редактировать картинку
if ($img && $foundUser['id'] == $user->id || $user->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` = " . $foundUser['id']);

    if ($req->rowCount()) {
        $res = $req->fetch();
        $album = $res['album_id'];
        echo '<div class="phdr"><a href="?act=show&amp;al=' . $album . '&amp;user=' . $foundUser['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Edit image') . '</div>';

        if (isset($_POST['submit'])) {
            if (! isset($_SESSION['post'])) {
                $_SESSION['post'] = true;
                $sql = '';
                $rotate = isset($_POST['rotate']) ? (int) ($_POST['rotate']) : 0;
                $brightness = isset($_POST['brightness']) ? (int) ($_POST['brightness']) : 0;
                $contrast = isset($_POST['contrast']) ? (int) ($_POST['contrast']) : 0;
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                $description = mb_substr($description, 0, 500);
                if ($rotate == 1 || $rotate == 2 || ($brightness > 0 && $brightness < 5) || ($contrast > 0 && $contrast < 5)) {
                    $path = UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/';
                    $handle = new \Verot\Upload\Upload($path . $res['img_name']);
                    // Обрабатываем основное изображение
                    $handle->file_new_name_body = 'img_' . time();

                    if ($rotate == 1 || $rotate == 2) {
                        $handle->image_rotate = ($rotate == 2 ? 90 : 270);
                    }

                    if ($brightness > 0 && $brightness < 5) {
                        switch ($brightness) {
                            case 1:
                                $handle->image_brightness = -40;
                                break;

                            case 2:
                                $handle->image_brightness = -20;
                                break;

                            case 3:
                                $handle->image_brightness = 20;
                                break;

                            case 4:
                                $handle->image_brightness = 40;
                                break;
                        }
                    }

                    if ($contrast > 0 && $contrast < 5) {
                        switch ($contrast) {
                            case 1:
                                $handle->image_contrast = -50;
                                break;

                            case 2:
                                $handle->image_contrast = -25;
                                break;

                            case 3:
                                $handle->image_contrast = 25;
                                break;

                            case 4:
                                $handle->image_contrast = 50;
                                break;
                        }
                    }

                    $handle->process($path);
                    $img_name = $handle->file_dst_name;

                    if ($handle->processed) {
                        // Обрабатываем превьюшку
                        $handle->file_new_name_body = 'tmb_' . time();

                        if ($rotate == 1 || $rotate == 2) {
                            $handle->image_rotate = ($rotate == 2 ? 90 : 270);
                        }

                        if ($brightness > 0 && $brightness < 5) {
                            switch ($brightness) {
                                case 1:
                                    $handle->image_brightness = -40;
                                    break;

                                case 2:
                                    $handle->image_brightness = -20;
                                    break;

                                case 3:
                                    $handle->image_brightness = 20;
                                    break;

                                case 4:
                                    $handle->image_brightness = 40;
                                    break;
                            }
                        }

                        if ($contrast > 0 && $contrast < 5) {
                            switch ($contrast) {
                                case 1:
                                    $handle->image_contrast = -50;
                                    break;

                                case 2:
                                    $handle->image_contrast = -25;
                                    break;

                                case 3:
                                    $handle->image_contrast = 25;
                                    break;

                                case 4:
                                    $handle->image_contrast = 50;
                                    break;
                            }
                        }

                        $handle->image_resize = true;
                        $handle->image_x = 100;
                        $handle->image_y = 100;
                        $handle->image_ratio_no_zoom_in = true;
                        $handle->process($path);
                        $tmb_name = $handle->file_dst_name;
                    }

                    $handle->clean();
                    @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name']);
                    @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name']);
                    $sql = '`img_name` = ' . $db->quote($img_name) . ', `tmb_name` = ' . $db->quote($tmb_name) . ',';
                }

                $db->exec("UPDATE `cms_album_files` SET ${sql}
                    `description` = " . $db->quote($description) . "
                    WHERE `id` = '${img}'
                ");
            }

            echo '<div class="gmenu"><p>' . _t('Image successfully changed') . '<br>' .
                '<a href="?act=show&amp;al=' . $album . '&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>';
        } else {
            unset($_SESSION['post']);
            echo '<form action="?act=image_edit&amp;img=' . $img . '&amp;user=' . $foundUser['id'] . '" method="post">' .
                '<div class="menu">' .
                '<p><h3>' . _t('Image') . '</h3>' .
                '<img src="../upload/users/album/' . $foundUser['id'] . '/' . $res['tmb_name'] . '" /></p>' .
                '<p><h3>' . _t('Description') . '</h3>' .
                '<textarea name="description" rows="' . $user->config->fieldHeight . '">' . $tools->checkout($res['description']) . '</textarea><br>' .
                '<small>' . _t('Optional field') . ', max. 500</small></p>' .
                '</div><div class="rmenu">' .
                '<p><h3>Яркость</h3>' .
                '<table border="0" cellspacing="0" cellpadding="0" style="text-align:center"><tr>' .
                '<td><input type="radio" name="brightness" value="1"/></td>' .
                '<td><input type="radio" name="brightness" value="2"/></td>' .
                '<td><input type="radio" name="brightness" value="0" checked="checked"/></td>' .
                '<td><input type="radio" name="brightness" value="3"/></td>' .
                '<td><input type="radio" name="brightness" value="4"/></td>' .
                '</tr><tr>' .
                '<td>-2</td>' .
                '<td>-1</td>' .
                '<td>0</td>' .
                '<td>+1</td>' .
                '<td>+2</td>' .
                '</tr></table></p>' .
                '<p><h3>Контрастность</h3>' .
                '<table border="0" cellspacing="0" cellpadding="0" style="text-align:center"><tr>' .
                '<td><input type="radio" name="contrast" value="1"/></td>' .
                '<td><input type="radio" name="contrast" value="2"/></td>' .
                '<td><input type="radio" name="contrast" value="0" checked="checked"/></td>' .
                '<td><input type="radio" name="contrast" value="3"/></td>' .
                '<td><input type="radio" name="contrast" value="4"/></td>' .
                '</tr><tr>' .
                '<td>-2</td>' .
                '<td>-1</td>' .
                '<td>0</td>' .
                '<td>+1</td>' .
                '<td>+2</td>' .
                '</tr></table></p>' .
                '<p><h3>' . _t('Rotate') . '</h3>' .
                '<input type="radio" name="rotate" value="0" checked="checked"/>&#160;' . _t('do not rotate') . '<br>' .
                '<input type="radio" name="rotate" value="2"/>&#160;' . _t('clockwise') . '<br>' .
                '<input type="radio" name="rotate" value="1"/>&#160;' . _t('counterclockwise') . '</p>' .
                '<p><small>' . _t('Note, you may lose the quality of an image if you change the brightness, contrast and rotate it for many times. Do not make any changes without a reason.') . '</small></p>' .
                '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
                '</div></form>' .
                '<div class="phdr"><a href="?act=show&amp;al=' . $album . '&amp;user=' . $foundUser['id'] . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
