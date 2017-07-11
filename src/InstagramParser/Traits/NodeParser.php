<?php

namespace Astrotomic\InstagramParser\Traits;

trait NodeParser
{
    protected function parseNode($node, $formattedUser = null)
    {
        $formattedUser = !empty($formattedUser) ? $formattedUser : null;
        if (!empty($node['owner']) && is_null($formattedUser)) {
            $formattedUser = $node['owner'];
        }

        $aspectRatio = $node['dimensions']['height'] / $node['dimensions']['width'];

        $media = [
            'attribution'  => null,
            'videos'       => null,
            'tags'         => null,
            'location'     => null,
            'comments'     => null,
            'filter'       => !empty($node['filter_name']) ? $node['filter_name'] : null,
            'created_time' => $node['date'],
            'link'         => 'https://www.instagram.com/p/'.$node['code'].'/',
            'likes'        => null,
            'images'       => [
                'low_resolution' => [
                    'url'    => $this->getDisplaySrcBySize($node['display_src'], 320, 320),
                    'width'  => 320,
                    'height' => $aspectRatio * 320,
                ],
                'standard_resolution' => [
                    'url'    => $this->getDisplaySrcBySize($node['display_src'], 640, 640),
                    'width'  => 640,
                    'height' => $aspectRatio * 640,
                ],
                '__original' => [
                    'url'    => $node['display_src'],
                    'width'  => $node['dimensions']['width'],
                    'height' => $node['dimensions']['height'],
                ],
            ],
            'users_in_photo' => null,
            'caption'        => null,
            'type'           => $node['is_video'] ? 'video' : 'image',
            'id'             => $node['id'].'_'.$formattedUser['id'],
            'code'           => $node['code'],
            'user'           => $formattedUser,
        ];
        if (array_key_exists('thumbnail_src', $node)) {
            $media['images']['thumbnail'] = [
                'url'    => $node['thumbnail_src'],
                'width'  => 640,
                'height' => 640,
            ];
        }

        if (!empty($node['caption'])) {
            $media['caption'] = [
                'created_time' => $node['date'],
                'text'         => $node['caption'],
                'from'         => $formattedUser,
            ];
            $media['tags'] = $this->parseTags($node['caption']);
        }

        if (!empty($node['video_url'])) {
            $media['videos'] = [
                'standard_resolution' => [
                    'url'    => $node['video_url'],
                    'width'  => 640,
                    'height' => $aspectRatio * 640,
                ],
            ];
        }

        if (!empty($node['comments'])) {
            $media['comments'] = [
                'count' => !empty($node['comments']['count']) ? $node['comments']['count'] : 0,
                'data'  => [],
            ];

            if (!empty($node['comments']['nodes'])) {
                $comments = $node['comments']['nodes'];
                foreach ($comments as $comment) {
                    $commentUser = null;
                    if (!empty($comment['user'])) {
                        $commentUser = [
                            'username'        => $comment['user']['username'],
                            'profile_picture' => $comment['user']['profile_pic_url'],
                            'id'              => $comment['user']['id'],
                        ];
                    }
                    $media['comments']['data'][] = [
                        'created_time' => $comment['created_at'],
                        'text'         => $comment['text'],
                        'from'         => $commentUser,
                    ];
                }
            }
        }
        if (!empty($node['likes'])) {
            $media['likes'] = [
                'count' => !empty($node['likes']['count']) ? $node['likes']['count'] : 0,
                'data'  => [],
            ];
            if (!empty($node['likes']['nodes'])) {
                $likes = $node['likes']['nodes'];
                foreach ($likes as $like) {
                    $likeUser = null;
                    if (!empty($like['user'])) {
                        $likeUser = [
                            'username'        => $like['user']['username'],
                            'profile_picture' => $like['user']['profile_pic_url'],
                            'id'              => $like['user']['id'],
                        ];
                    }
                    $media['likes']['data'][] = $likeUser;
                }
            }
        }
        if (!empty($node['location'])) {
            $media['location'] = [
                'name' => $node['location']['name'],
                'id'   => $node['location']['id'],
            ];
        }

        return $media;
    }

    protected function getDisplaySrcBySize($displaySrc, $width, $height)
    {
        if (preg_match('#/s\d+x\d+/#', $displaySrc)) {
            return preg_replace('#/s\d+x\d+/#', '/s'.$width.'x'.$height.'/', $displaySrc);
        } elseif (preg_match('#/e\d+/#', $displaySrc)) {
            return preg_replace('#/e(\d+)/#', '/s'.$width.'x'.$height.'/e$1/', $displaySrc);
        } elseif (preg_match('#(\.com/[^/]+)/#', $displaySrc)) {
            return preg_replace('#(\.com/[^/]+)/#', '$1/s'.$width.'x'.$height.'/', $displaySrc);
        }

        return null;
    }

    protected function parseTags($caption)
    {
        preg_match_all('#\#([\w_]+)#u', $caption, $tags);

        return $tags[1];
    }
}
