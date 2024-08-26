<?php
namespace Swaminathan\Testimonials\Api;

/**
 * Interface UrlRewriteInterface
 *
 * @api
 */
interface TestimonialInterface
{
    /**
     * Returns list of active testimonials if enabled.
     * 
     * @return array
     */
    public function getTestimonials();

    /**
     * inserts the testimonial.
     * @param string[] $data
     * @return array
     */
    public function addTestimonials($data);
}