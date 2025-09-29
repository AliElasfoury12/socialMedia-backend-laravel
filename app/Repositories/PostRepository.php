<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use PDO;
use stdClass;

class PostRepository
{
    public stdClass $nextCursor;

    public function getPosts (int $auth_id, int $per_page, string|null $cursor): array  
    {
        $last_id = $this->get_last_id($cursor);
        $posts = $this->fetch_posts($auth_id,$per_page,$last_id);
        $this->setNextCusror($posts, $per_page);
        $posts_images = $this->fetch_post_images($posts);
        $posts_images_index = $this->indexPostsImages($posts_images);
        return $this->formatPosts($posts, $posts_images_index);
    }

    private function query (int $auth_id, int $per_page, int $last_id): string  
    {
        $offest = $last_id ? "AND posts.id < $last_id" : '';

        return "SELECT posts.id, posts.content,posts.created_at,
        users.id AS user_id, users.name AS user_name, users_profile_images.url AS user_profile_pic_url,
        likes.user_id AS is_liked_by_auth_user,
        shared_post.id AS shared_post_id, shared_post.content AS shared_post_content,
        shared_post.created_at AS shared_post_created_at,
        shared_post_user.id AS shared_post_user_id, shared_post_user.name AS shared_post_user_name, shared_post_user_profile_image.url AS shared_post_user_profile_pic_url,
        (select count(*) from likes where posts.id = likes.post_id) AS likes_count,
        (SELECT COUNT(*) from comments WHERE posts.id = comments.post_id) AS comments_count
        from posts 
        LEFT JOIN users ON posts.user_id = users.id
        LEFT JOIN users_profile_images ON users.profile_image_id = users_profile_images.id
        LEFT JOIN likes ON likes.post_id = posts.id AND likes.user_id = $auth_id
        LEFT JOIN shared_posts ON posts.id = shared_posts.post_id
        LEFT JOIN posts AS shared_post ON shared_post.id = shared_posts.shared_post_id
        LEFT JOIN users AS shared_post_user ON shared_post.user_id = shared_post_user.id
        LEFT JOIN users_profile_images AS shared_post_user_profile_image ON shared_post_user.profile_image_id = shared_post_user_profile_image.id
        where posts.user_id = users.id $offest ORDER BY posts.created_at DESC LIMIT $per_page ";
    }

    private function fetch (string $query): array 
    {
        return DB::getPdo()->query($query, PDO::FETCH_ASSOC)->fetchAll();
    }

    private function fetch_posts (int $auth_id, int $per_page, int $last_id): array 
    {
        $query = $this->query($auth_id, ++$per_page, $last_id);
        return $this->fetch($query);
    }

    private function collectIds (array $posts): string 
    {
        $posts_ids = '';
        $helper = [];

        foreach ($posts as $post) {
            $helper[$post['id']] = $post['id'];
            $posts_ids .= $post['id'].',';
            if($post['shared_post_id'] && !isset($helper[$post['id']])) 
                $post_ids .= $post['shared_post_id'].',';
        }

        return trim($posts_ids, ',');
    }

    private function getPostImages (string $posts_ids): array 
    {
        $query = "SELECT id,img,post_id from post_imgs WHERE post_id IN ($posts_ids)";
        return $this->fetch($query);
    }

    private function fetch_post_images (array $posts): array 
    {
        $post_ids = $this->collectIds($posts);
        return $this->getPostImages($post_ids);
    }

    private function indexPostsImages (array $posts_images): array 
    {
        $post_images_index = [];
        $FK = 'post_id';

        foreach ($posts_images as $image) {
            $newImage = $image;
            unset($newImage['post_id']);
            if(isset($post_images_index[$image[$FK]])) $post_images_index[$image[$FK]][] = $newImage;
            else $post_images_index[$image[$FK]] = [$newImage];
        }

        return $post_images_index;
    }

    private function formatPosts (array $posts, array $post_images_index ): array 
    {
        foreach($posts as &$post) {
            $content = $post['content'];
            
            $post['user'] = [
                'id' => $post['user_id'],
                'name' => $post['user_name'],
                'profile_pic' => ['url' => $post['user_profile_pic_url']]
            ];

            $post['content'] = strlen($content) > 80 ? substr($content,0,80).'...' : $content;

            $post['post_imgs'] = $post_images_index[$post['id']] ?? [];
            $post['shared_post'] = $post['shared_post_id'] ? $this->formatSharedPost($post): [];
            $this->deleteUnsedFromPost($post);
        }

        return $posts;
    }

    private function formatSharedPost (array $post): array 
    {
        $content = $post['shared_post_content'];
        return [
            'id' => $post['shared_post_id'],
            'content' => strlen($content) > 80 ? substr($content,0,80).'...' : $content,
            'created_at' => $post['shared_post_created_at'],
            'post_imgs' => $post_images_index[$post['shared_post_id']] ?? [] ,
            'user' => [
                'id' => $post['shared_post_user_id'],
                'name' => $post['shared_post_user_name'],
                'profile_pic' => ['url' => $post['shared_post_user_profile_pic_url']]
            ]
        ];
    }

    private function deleteUnsedFromPost (array &$post): void 
    {
        unset(
            $post['user_id'], 
            $post['user_name'], 
            $post['user_profile_pic_url'],
            $post['post_img_id'], 
            $post['img'],
            $post['shared_post_id'], 
            $post['shared_post_content'],
            $post['shared_post_created_at'],
            $post['shared_post_user_id'],
            $post['shared_post_user_name'],
            $post['shared_post_user_profile_pic_url']
        );
    }

    private function setNextCusror (array &$posts, int $per_page): void 
    {
        $this->nextCursor = new stdClass;
        $this->nextCursor->id = $posts[$per_page - 1]['id'];
        if(isset($posts[$per_page])) {
            $this->nextCursor->isNextItems = true;
            unset($posts[$per_page]);
        }else $this->nextCursor->isNextItems = null;
    }

    public function get_next_cursor (): string|null  
    {
        if($this->nextCursor->isNextItems === null) return null;
        return $this->base64url_encode(json_encode($this->nextCursor));
    }

    private function get_last_id (string|null $cursor): int 
    {
        $cursor = $cursor ? json_decode($this->base64url_decode($cursor)) : '';
        return $cursor?->id ?? 0;
    }

    private function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode(string $data): string 
    {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
}
