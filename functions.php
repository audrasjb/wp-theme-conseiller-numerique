<?php
/**
 * Fonctions
 */

add_theme_support('title-tag');
wp_enqueue_style('dsfr');
wp_enqueue_style('utilities');
wp_enqueue_style('custom');

/**
 * Filtrage des posts selon tag / catégorie 
 * 
 * @param $request  requete entrante
 * @param $my_posts liste des posts
 * 
 * @return posts
 */
function getPostsFiltered($request, $my_posts) 
{
    if (have_posts()) {  
        //Filtre pas tag ou catégorie
        if (is_tag()) {
            $tagFiltered = explode('/', $request)[1]; //on prend le query param 1
            $my_posts = array_filter(
                $my_posts, 
                function ($post) use ($tagFiltered) {
                    $tags = wp_get_post_tags($post->ID);
                    if (count($tags) > 0) {
                        foreach ($tags as $tag) {
                            if ($tag->slug === $tagFiltered) {
                                  return true;
                            }
                        }
                    }
                    return false;
                }
            );
        } elseif (is_category()) {
            //Si pagination => suppression des query params correspondants (au cas où hiérarchie des catégories)
            if (is_paged()) {
                $categoryFiltered = explode('/', $request);
                array_pop($categoryFiltered);
                array_pop($categoryFiltered);
                $categoryFiltered = implode('/', $categoryFiltered);
                $categoryFiltered = array_pop(explode('/', $categoryFiltered));
            } else {
                $categoryFiltered = array_pop(explode('/', $request)); 
            }
            $my_posts = array_filter(
                $my_posts,
                function ($post) use ($categoryFiltered) {
                    $categories = wp_get_post_categories($post->ID);
                    if (count($categories) > 0) {
                        foreach ($categories as $key => $categorie) {
                            if (get_category_by_slug($categoryFiltered)->name === get_cat_name($categorie)) {
                                return true;
                            }
                        }
                    }
                    return false;
                }
            );
        }

        //flag du premier post de la première page pour son affichage différent
        if (get_query_var('paged') === 0) {
            $my_posts[0]->first = true;
        }

        return $my_posts;
    }
}

/**
 * Récupération de la durée de la vidéo si présente dans le post
 * 
 * @param $postId id du post
 * 
 * @return string
 */
function getDurationFirstVideo($postId)
{
    $post = get_post($post_id);
    $content = do_shortcode(apply_filters('the_content', $post->post_content));
    $embeds = get_media_embedded_in_content($content);

    if (!empty($embeds)) {
        foreach ($embeds as $embed) {
            //Prend la durée de le première vidéo embed trouvée
            if (strpos($embed, 'video')) {
                $urlFirstVideo = substr($embed, strpos($embed, "wp-content"));
                $urlFirstVideo = substr($urlFirstVideo, 0, strpos($urlFirstVideo, "\""));
                include_once ABSPATH . 'wp-admin/includes/media.php';
                return ' - lecture : ' . human_readable_duration(gmdate('i:s', wp_read_video_metadata($urlFirstVideo)['length']));
            }
        }
    } else {
        //pas de vidéo "embed" trouvée
        return '';
    }
}

/**
 * Tri par ordre alphabétique
 * 
 * @param $a nom1
 * @param $b nom2
 * 
 * @return int
 */
function cmp($a, $b)
{
    if ($a->name < $b->name) {
        return -1;
    } else if ($a->name > $b->name) {
        return 1;
    }
    return 0;
}