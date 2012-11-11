<?php
/**
 * Forums Gadget
 *
 * @category    Gadget
 * @package     Forums
 * @author      Ali Fazelzadeh <afz@php.net>
 * @author      Hamid Reza Aboutalebi <abt_am@yahoo.com>
 * @copyright   2012 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Forums_Actions_Posts extends ForumsHTML
{
    /**
     * Display topic's posts
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function Posts()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('fid', 'tid', 'page'), 'get');
        $page = empty($rqst['page'])? 1 : (int)$rqst['page'];

        $fModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
        $topic = $fModel->GetTopic($rqst['tid'], $rqst['fid']);
        if (Jaws_Error::IsError($topic) || empty($topic)) {
            return false;
        }

        $limit = (int)$GLOBALS['app']->Registry->Get('/gadgets/Forums/posts_limit');
        $pModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Posts');
        $posts = $pModel->GetPosts($rqst['tid'], $limit, ($page - 1) * $limit);
        if (Jaws_Error::IsError($posts)) {
            return false;
        }

        $res = $fModel->UpdateTopicViews($topic['id']);
        if (Jaws_Error::IsError($res)) {
            // do nothing
        }

        $tpl = new Jaws_Template('gadgets/Forums/templates/');
        $tpl->Load('Posts.html');
        $tpl->SetBlock('posts');

        $tpl->SetVariable('title', $topic['subject']);
        $tpl->SetVariable('url', $this->GetURLFor('Posts', array('fid' => $rqst['fid'], 'tid' => $rqst['tid'])));

        $objDate = $GLOBALS['app']->loadDate();
        foreach ($posts as $pnum => $post) {
            $tpl->SetBlock('posts/post');
            $tpl->SetVariable('title', $topic['subject']);
            $tpl->SetVariable('posts_count_lbl',_t('FORUMS_USERS_POSTS_COUNT'));
            $tpl->SetVariable('registered_date_lbl',_t('FORUMS_USERS_REGISTERED_DATE'));
            $tpl->SetVariable('postedby_lbl',_t('FORUMS_POSTEDBY'));
            $tpl->SetVariable('posts_count', $pModel->GetUserPostsCount($post['uid']));
            $tpl->SetVariable('registered_date', $objDate->Format($post['user_registered_date']));
            $tpl->SetVariable('createtime', $objDate->Format($post['createtime']));
            $tpl->SetVariable('message',  $post['message']);
            $tpl->SetVariable('username', $post['username']);
            $tpl->SetVariable('nickname', $post['nickname']);
            $tpl->SetVariable(
                'user_url',
                $GLOBALS['app']->Map->GetURLFor(
                    'Users',
                    'Profile',
                    array('user' => $post['username'])
                )
            );

            // update information
            if ($post['last_update_uid'] != 0) {
                $tpl->SetBlock('posts/post/update');
                $tpl->SetVariable('updatedby_lbl', _t('FORUMS_POSTS_UPDATEDBY'));
                $tpl->SetVariable('username', $post['username']);
                $tpl->SetVariable('nickname', $post['nickname']);
                $tpl->SetVariable(
                    'user_url',
                    $GLOBALS['app']->Map->GetURLFor(
                        'Users',
                        'Profile',
                        array('user' => $post['username'])
                    )
                );
                $tpl->SetVariable('update_reason', $post['last_update_reason']);
                $tpl->SetVariable('update_time', $objDate->Format($post['last_update_time']));
                $tpl->ParseBlock('posts/post/update');
            }
            // Check User Can Edit Posts
            $tpl->SetBlock('posts/post/actions');
            $tpl->SetVariable('editpost_lbl',_t('FORUMS_POSTS_EDIT'));
            $tpl->SetVariable('deletepost_lbl',_t('FORUMS_POSTS_DELETE'));
            if ($pnum == 0) {
                // topic action links
                $tpl->SetVariable(
                    'editpost_url',
                    $this->GetURLFor(
                        'EditTopic',
                        array('fid' => $rqst['fid'], 'tid' => $rqst['tid'])
                    )
                );
                $tpl->SetVariable(
                    'deletepost_url',
                    $this->GetURLFor(
                        'DeleteTopic',
                        array('fid' => $rqst['fid'], 'tid' => $rqst['tid'])
                    )
                );
            } else {
                // post action links
                $tpl->SetVariable(
                    'editpost_url',
                    $this->GetURLFor(
                        'EditPost',
                        array('fid' => $rqst['fid'], 'tid' => $rqst['tid'], 'pid' => $post['id'])
                    )
                );
                $tpl->SetVariable(
                    'deletepost_url',
                    $this->GetURLFor(
                        'DeletePost',
                        array('fid' => $rqst['fid'], 'tid' => $rqst['tid'], 'pid' => $post['id'])
                    )
                );
            }
            $tpl->ParseBlock('posts/post/actions');
            $tpl->ParseBlock('posts/post');
        }

        $tpl->SetBlock('posts/actions');
        $tpl->SetVariable('newpost_lbl', _t('FORUMS_POSTS_NEW'));
        $tpl->SetVariable(
            'newpost_url',
            $this->GetURLFor('NewPost', array('fid' => $rqst['fid'], 'tid' => $rqst['tid']))
        );
        $tpl->SetVariable(
            'locktopic_lbl',
            $topic['locked']? _t('FORUMS_TOPICS_UNLOCK') : _t('FORUMS_TOPICS_LOCK')
        );
        $tpl->SetVariable(
            'locktopic_url',
            $this->GetURLFor('LockTopic', array('fid' => $rqst['fid'], 'tid' => $rqst['tid']))
        );
        $tpl->ParseBlock('posts/actions');

        $tpl->ParseBlock('posts');
        return $tpl->Get();
    }

    /**
     * Show new post form
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function NewPost()
    {
        return $this->EditPost();
    }

    /**
     * Show edit post form
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function EditPost()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('fid', 'tid', 'pid'), 'get');
        if (empty($rqst['fid']) || empty($rqst['tid'])) {
            return false;
        }

        $tModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Topics');
        $topic = $tModel->GetTopic($rqst['tid'], $rqst['fid']);
        if (Jaws_Error::IsError($topic) || empty($topic)) {
            return false;
        }

        if (!empty($rqst['pid'])) {
            $pModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Posts');
            $post = $pModel->GetPost($rqst['pid']);
            if (Jaws_Error::IsError($post) || empty($post)) {
                return false;
            }

            $title = _t('FORUMS_POST_EDIT_TITLE');
            $btn_title = _t('FORUMS_POST_EDIT_BUTTON');
        } else {
            $post = array();
            $post['id'] = 0;
            $post['message'] = '';
            $post['last_update_reason'] = '';
            $title = _t('FORUMS_POST_ADD_TITLE');
            $btn_title = _t('FORUMS_POST_ADD_BUTTON');
        }

        $tpl = new Jaws_Template('gadgets/Forums/templates/');
        $tpl->Load('EditPost.html');
        $tpl->SetBlock('post');

        $tpl->SetVariable('topic_title', $topic['subject']);
        $tpl->SetVariable(
            'topic_url',
            $this->GetURLFor('Posts', array('fid' => $topic['fid'], 'tid' => $topic['id']))
        );
        $tpl->SetVariable('title', $title);
        $tpl->SetVariable('fid', $topic['fid']);
        $tpl->SetVariable('tid', $topic['id']);
        $tpl->SetVariable('pid', $post['id']);

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Forums')) {
            $tpl->SetBlock('post/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('post/response');
        }

        // message
        $tpl->SetVariable('message', $post['message']);
        $tpl->SetVariable('lbl_message', _t('FORUMS_POST_MESSAGE'));

        // update reason
        if (!empty($post['id'])) {
            $tpl->SetBlock('post/update_reason');
            $tpl->SetVariable('lbl_update_reason', _t('FORUMS_POST_UPDATE_REASON'));
            $tpl->SetVariable('update_reason', $post['last_update_reason']);
            $tpl->ParseBlock('post/update_reason');
        }

        // button
        $tpl->SetVariable('btn_title', $btn_title);

        $tpl->ParseBlock('post');
        return $tpl->Get();
    }

    /**
     * Add/Edit a post
     *
     * @access  public
     */
    function UpdatePost()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(
            array('fid', 'tid', 'pid', 'subject', 'message', 'update_reason'),
            'post'
        );

        $pModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Posts');
        if (empty($post['pid'])) {
            $result = $pModel->InsertPost(
                $GLOBALS['app']->Session->GetAttribute('user'),
                $post['tid'],
                $post['fid'],
                $post['message']
            );
        } else {
            $result = $pModel->UpdatePost(
                $post['pid'],
                $GLOBALS['app']->Session->GetAttribute('user'),
                $post['message'],
                $post['update_reason']
            );
        }

        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse($result->getMessage(),
                                                         'Post');
        } else {
            $post['pid'] = $result;
            $GLOBALS['app']->Session->PushSimpleResponse(_t('FORUMS_POST_UPDATED'),
                                                         'Post');
        }

        // Redirect
        Jaws_Header::Location(
            $this->GetURLFor(
                'EditPost',
                array('fid' => $post['fid'], 'tid' => $post['tid'], 'pid' => $post['pid'])
            ),
            true
        );
    }

    /**
     * Delete a post
     *
     * @access  public
     */
    function DeletePost()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('fid', 'tid', 'pid', 'confirm'));

        $pModel = $GLOBALS['app']->LoadGadget('Forums', 'Model', 'Posts');
        $post = $pModel->GetPost($rqst['pid'], $rqst['tid'], $rqst['fid']);
        if (Jaws_Error::IsError($post) || empty($post) || $post['id'] == $post['topic_first_post_id']) {
            return false;
        }

        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            if (!empty($rqst['confirm'])) {
                $result = $pModel->DeletePost(
                    $post['id'],
                    $post['tid'],
                    $post['fid'],
                    $post['topic_last_post_id'],
                    $post['topic_last_post_time'],
                    $post['forum_last_topic_id']
                );
                if (Jaws_Error::IsError($result)) {
                    // redirect to post delete form
                    Jaws_Header::Location(
                        $this->GetURLFor(
                            'DeletePost',
                            array('fid' => $post['fid'], 'tid' => $post['tid'], 'pid' => $post['id'])
                        ),
                        true
                    );
                }
            }

            // redirect to topic posts list
            Jaws_Header::Location(
                $this->GetURLFor(
                    'Posts',
                    array('fid'=> $post['fid'], 'tid' => $post['tid'])
                ),
                true
            );
        } else {
            $tpl = new Jaws_Template('gadgets/Forums/templates/');
            $tpl->Load('DeletePost.html');
            $tpl->SetBlock('post');

            $tpl->SetVariable('fid', $post['fid']);
            $tpl->SetVariable('tid', $post['tid']);
            $tpl->SetVariable('pid', $post['id']);
            $tpl->SetVariable('topic_title', $post['subject']);
            $tpl->SetVariable(
                'topic_url',
                $this->GetURLFor('Posts', array('fid'=> $post['fid'], 'tid' => $post['tid']))
            );
            $tpl->SetVariable('title', _t('FORUMS_DELETE_POST'));
            $tpl->SetVariable('message', $post['message']);

            $tpl->SetVariable('postedby_lbl',_t('FORUMS_POSTEDBY'));
            $tpl->SetVariable('username', $post['username']);
            $tpl->SetVariable('nickname', $post['nickname']);
            $tpl->SetVariable(
                'user_url',
                $GLOBALS['app']->Map->GetURLFor('Users', 'Profile', array('user' => $post['username']))
            );
            $objDate = $GLOBALS['app']->loadDate();
            $tpl->SetVariable('createtime', $objDate->Format($post['createtime']));

            $tpl->SetVariable('btn_submit_title', _t('GLOBAL_DELETE'));
            $tpl->SetVariable('btn_cancel_title', _t('GLOBAL_CANCEL'));
            $tpl->ParseBlock('post');
            return $tpl->Get();
        }
    }

}