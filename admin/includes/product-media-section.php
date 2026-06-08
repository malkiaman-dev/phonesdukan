<?php
/**
 * Shared Product Media form section for add/edit product pages.
 *
 * Expected vars (optional):
 * - $pmWrapperClass: outer wrapper class (form-card or ep-card)
 * - $pmHeadingTag: h2 or h3
 */
$pmWrapperClass = $pmWrapperClass ?? 'form-card';
$pmHeadingTag = $pmHeadingTag ?? 'h2';
?>
<section class="<?= htmlspecialchars($pmWrapperClass) ?> pm-section" id="productMediaSection">
    <<?= $pmHeadingTag ?> style="margin-bottom:16px">Product Media</<?= $pmHeadingTag ?>>
    <p class="pm-subtitle">Add an optional product video and arrange gallery display order.</p>

    <div class="form-group full-width">
        <label>Video Source</label>
        <div class="pm-source-grid">
            <label class="pm-source-option">
                <input type="radio" name="video_source" value="upload" checked>
                <span>Upload Video</span>
            </label>
            <label class="pm-source-option">
                <input type="radio" name="video_source" value="youtube">
                <span>YouTube URL</span>
            </label>
            <label class="pm-source-option">
                <input type="radio" name="video_source" value="vimeo">
                <span>Vimeo URL</span>
            </label>
            <label class="pm-source-option">
                <input type="radio" name="video_source" value="mp4">
                <span>External MP4 URL</span>
            </label>
        </div>
    </div>

    <div id="pm-upload-panel" class="pm-panel is-active">
        <label class="pm-upload-box" for="pm_video_file">
            <span class="pm-upload-icon"><i class="fas fa-film"></i></span>
            <span class="pm-upload-title">Upload Video</span>
            <span class="pm-upload-help">MP4, WebM, MOV — max 100 MB</span>
        </label>
        <input class="pm-file-input" type="file" id="pm_video_file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
        <input class="pm-file-input" type="file" id="pm_video_file_replace" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">

        <div id="pm_upload_progress" class="pm-progress-wrap">
            <div class="pm-progress-bar"><div id="pm_upload_progress_fill" class="pm-progress-fill"></div></div>
            <div class="pm-progress-label"><span>Uploading...</span><span id="pm_upload_progress_text">0%</span></div>
        </div>
    </div>

    <div id="pm-url-panel" class="pm-panel">
        <label for="pm_video_url">Video URL</label>
        <div class="pm-url-field">
            <input type="url" id="pm_video_url" placeholder="https://youtube.com/...">
            <button type="button" class="pm-btn" id="pm_validate_url_btn">Validate</button>
        </div>
        <div id="pm_url_error" class="pm-url-error"></div>
    </div>

    <div id="pm_video_preview" class="pm-preview-card">
        <div id="pm_preview_media" class="pm-preview-media"></div>
        <div class="pm-preview-actions">
            <button type="button" class="pm-btn" id="pm_replace_video_btn"><i class="fas fa-sync-alt"></i> Replace</button>
            <button type="button" class="pm-btn pm-btn-outline" id="pm_remove_video_btn"><i class="fas fa-trash"></i> Remove</button>
        </div>
    </div>

    <div class="pm-thumb-section form-group full-width">
        <label>Video Thumbnail <small style="color:#9ca3af;font-weight:400">(optional poster)</small></label>
        <p class="pm-thumb-hint">Upload a custom poster image, or use the auto-generated frame from your video.</p>

        <div id="pm-thumb-upload-panel" class="pm-panel is-active">
            <label class="pm-upload-box" for="pm_thumb_file">
                <span class="pm-upload-icon"><i class="fas fa-image"></i></span>
                <span class="pm-upload-title">Upload Thumbnail</span>
                <span class="pm-upload-help">JPG, PNG, WEBP — max 5 MB</span>
            </label>
            <input class="pm-file-input" type="file" id="pm_thumb_file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
            <input class="pm-file-input" type="file" id="pm_thumb_file_replace" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">

            <div id="pm_thumb_upload_progress" class="pm-progress-wrap">
                <div class="pm-progress-bar"><div id="pm_thumb_upload_progress_fill" class="pm-progress-fill"></div></div>
                <div class="pm-progress-label"><span>Uploading...</span><span id="pm_thumb_upload_progress_text">0%</span></div>
            </div>
        </div>

        <div id="pm_thumb_preview_card" class="pm-preview-card pm-thumb-preview-card">
            <div class="pm-preview-media pm-thumb-preview-media">
                <img id="pm_thumb_preview_img" alt="Video thumbnail preview">
                <span id="pm_thumb_badge" class="pm-thumb-badge"></span>
            </div>
            <div class="pm-preview-actions">
                <button type="button" class="pm-btn" id="pm_replace_thumb_btn"><i class="fas fa-sync-alt"></i> Replace</button>
                <button type="button" class="pm-btn pm-btn-outline" id="pm_remove_thumb_btn"><i class="fas fa-trash"></i> Remove</button>
            </div>
        </div>
    </div>

    <div class="pm-gallery-order">
        <h4>Product Gallery Order</h4>
        <p>Drag and drop to set display order. Video appears alongside images in the product gallery.</p>
        <p id="pm_gallery_order_empty" class="pm-order-empty">Add images or a video to configure gallery order.</p>
        <ul id="pm_gallery_order_list" class="pm-order-list"></ul>
    </div>

    <input type="hidden" name="gallery_order_json" id="gallery_order_json" value="[]">
    <input type="hidden" name="video_uploaded_path" id="video_uploaded_path" value="">
    <input type="hidden" name="video_url_hidden" id="video_url_hidden" value="">
    <input type="hidden" name="video_thumbnail_path" id="video_thumbnail_path" value="">
    <input type="hidden" name="video_custom_thumbnail_path" id="video_custom_thumbnail_path" value="">
    <input type="hidden" name="remove_custom_thumbnail_flag" id="remove_custom_thumbnail_flag" value="0">
    <input type="hidden" name="remove_video_flag" id="remove_video_flag" value="0">
    <input type="hidden" name="has_product_video" id="has_product_video" value="0">
</section>
