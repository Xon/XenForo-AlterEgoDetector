<?php

/**
 * Copyright 2014 Liam Williams
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
class LiamW_AlterEgoDetector_ReportHandler_AlterEgo extends XenForo_ReportHandler_Abstract
{

    public function getReportDetailsFromContent(array $content)
    {
        $user_id1 = isset($content[0]['user_id']) ? $content[0]['user_id'] : 0;
        //$user_id2 = isset($content[1]['user_id']) ? $content[1]['user_id'] : 0;

        return array(
            $user_id1,
            $user_id1,
            array(
                $content
            )
        );
    }

    public function getVisibleReportsForUser(array $reports, array $viewingUser)
    {
        foreach ($reports AS $reportId => $report)
        {
            if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'aedviewreport'))
            {
                unset($reports[$reportId]);
            }
        }

        return $reports;
    }

    public function getContentLink(array $report, array $contentInfo)
    {
        return null;
    }

    public function getContentForThread(array $report, array $contentInfo)
    {
        if (!isset($report['extraContent']))
        {
            $report['extraContent'] = $this->prepareExtraContent($contentInfo);
        }
        $content = parent::getContentForThread($report, $contentInfo);

        $users = @$report['extraContent'][0];
        if (empty($users) || !is_array($users))
        {
            $users = array();
        }
        if ($users)
        {
            foreach($users as &$user)
            {
                if (!isset($user['username']))
                {
                    $user['username'] = 'guest';
                }
                if (!isset($user['user_id']))
                {
                    $user['user_id'] = 0;
                }
            }
            $content['message'] = $this->_getSpamPreventionModel()->buildUserDetectionReportBody($users[0], array_slice($users, 1));
        }

        return $content;
    }

    public function getContentTitle(array $report, array $contentInfo)
    {
        if (!isset($report['extraContent']))
        {
            $report['extraContent'] = $this->prepareExtraContent($contentInfo);
        }
        $users = @$report['extraContent'][0];
        if (empty($users) || !is_array($users))
        {
            $users = array();
        }
        foreach($users as &$user)
        {
            if (!isset($user['username']))
            {
                $user['username'] = 'guest';
            }
            if (!isset($user['user_id']))
            {
                $user['user_id'] = 0;
            }
        }

        $AE_count = count($users) - 1;
        $username1 = @$users[0]['username'];
        $detectionType = empty($users[0]['detectionType']) ? '' : $users[0]['detectionType'];
        if ($AE_count <= 0)
        {
            return new XenForo_Phrase('aed_thread_subject_count', array(
                'username' => 'Guest',
                'count' => 0,
                'detectionType' => $detectionType,
            ));
        }
        else if ($AE_count == 1)
        {
            $username2 = $users[1]['username'];
            return new XenForo_Phrase('aed_thread_subject', array(
                'username1' => $username1,
                'username2' => $username2,
                'detectionType' => $detectionType,
            ));
        }
        else
        {
            return new XenForo_Phrase('aed_thread_subject_count', array(
                'username' => $username1,
                'count' => $AE_count,
                'detectionType' => $detectionType,
            ));
        }
    }

    /** @var LiamW_AlterEgoDetector_XenForo_Model_SpamPrevention */
    protected $_spamPreventionModel = null;

    /**
     * @return LiamW_AlterEgoDetector_XenForo_Model_SpamPrevention|XenForo_Model
     */
    protected function _getSpamPreventionModel()
    {
        if (empty($this->_spamPreventionModel))
        {
            $this->_spamPreventionModel = XenForo_Model::create('XenForo_Model_SpamPrevention');
        }
        return $this->_spamPreventionModel;
    }
}
