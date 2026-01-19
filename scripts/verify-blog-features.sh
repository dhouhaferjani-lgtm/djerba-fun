#!/bin/bash

# Blog Gallery & Takeaways Feature Verification Script
# Run this after deploying to verify the new features work correctly

set -e

API_URL="${API_URL:-http://localhost:8000/api/v1}"
FRONTEND_URL="${FRONTEND_URL:-http://localhost:3000}"

echo "=============================================="
echo "Blog Gallery & Takeaways Verification Script"
echo "=============================================="
echo ""
echo "API URL: $API_URL"
echo "Frontend URL: $FRONTEND_URL"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    FAILED=1
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

FAILED=0

echo "1. Checking API Health..."
echo "-------------------------"
if curl -s "$API_URL/../health" | grep -q "ok"; then
    pass "API is healthy"
else
    fail "API health check failed"
fi

echo ""
echo "2. Testing Blog List Endpoint..."
echo "---------------------------------"
BLOG_LIST=$(curl -s "$API_URL/blog/posts")

if echo "$BLOG_LIST" | grep -q '"headerStyle"'; then
    pass "headerStyle field present in blog list"
else
    fail "headerStyle field missing from blog list"
fi

if echo "$BLOG_LIST" | grep -q '"galleryImages"'; then
    pass "galleryImages field present in blog list"
else
    fail "galleryImages field missing from blog list"
fi

if echo "$BLOG_LIST" | grep -q '"keyTakeaways"'; then
    pass "keyTakeaways field present in blog list"
else
    fail "keyTakeaways field missing from blog list"
fi

echo ""
echo "3. Testing Single Post Endpoint..."
echo "-----------------------------------"

# Get first post slug
FIRST_SLUG=$(echo "$BLOG_LIST" | grep -o '"slug":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -n "$FIRST_SLUG" ]; then
    SINGLE_POST=$(curl -s "$API_URL/blog/posts/$FIRST_SLUG")

    if echo "$SINGLE_POST" | grep -q '"headerStyle"'; then
        pass "Single post has headerStyle"
    else
        fail "Single post missing headerStyle"
    fi

    # Check default value
    HEADER_STYLE=$(echo "$SINGLE_POST" | grep -o '"headerStyle":"[^"]*"' | cut -d'"' -f4)
    if [ "$HEADER_STYLE" = "image" ] || [ "$HEADER_STYLE" = "gallery" ] || [ "$HEADER_STYLE" = "none" ]; then
        pass "headerStyle has valid value: $HEADER_STYLE"
    else
        fail "headerStyle has invalid value: $HEADER_STYLE"
    fi
else
    warn "No blog posts found to test single post endpoint"
fi

echo ""
echo "4. Testing Existing Fields (Regression)..."
echo "-------------------------------------------"

if echo "$BLOG_LIST" | grep -q '"title"'; then
    pass "title field still present"
else
    fail "title field missing (REGRESSION)"
fi

if echo "$BLOG_LIST" | grep -q '"slug"'; then
    pass "slug field still present"
else
    fail "slug field missing (REGRESSION)"
fi

if echo "$BLOG_LIST" | grep -q '"excerpt"'; then
    pass "excerpt field still present"
else
    fail "excerpt field missing (REGRESSION)"
fi

if echo "$BLOG_LIST" | grep -q '"featuredImage"'; then
    pass "featuredImage field still present"
else
    fail "featuredImage field missing (REGRESSION)"
fi

if echo "$BLOG_LIST" | grep -q '"author"'; then
    pass "author field still present"
else
    fail "author field missing (REGRESSION)"
fi

if echo "$BLOG_LIST" | grep -q '"seo"'; then
    pass "seo field still present"
else
    fail "seo field missing (REGRESSION)"
fi

echo ""
echo "5. Testing Translation (Accept-Language)..."
echo "--------------------------------------------"

if [ -n "$FIRST_SLUG" ]; then
    EN_POST=$(curl -s -H "Accept-Language: en" "$API_URL/blog/posts/$FIRST_SLUG")
    FR_POST=$(curl -s -H "Accept-Language: fr" "$API_URL/blog/posts/$FIRST_SLUG")

    # Both should return 200
    if echo "$EN_POST" | grep -q '"data"'; then
        pass "English locale works"
    else
        fail "English locale failed"
    fi

    if echo "$FR_POST" | grep -q '"data"'; then
        pass "French locale works"
    else
        fail "French locale failed"
    fi
fi

echo ""
echo "6. Testing Featured Posts Endpoint..."
echo "--------------------------------------"
FEATURED=$(curl -s "$API_URL/blog/posts/featured")

if echo "$FEATURED" | grep -q '"headerStyle"'; then
    pass "Featured posts include new fields"
else
    warn "Featured posts may not have posts or new fields"
fi

echo ""
echo "=============================================="
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All API checks passed!${NC}"
else
    echo -e "${RED}Some checks failed. Please review above.${NC}"
fi
echo "=============================================="

echo ""
echo "MANUAL VERIFICATION CHECKLIST:"
echo "=============================="
echo ""
echo "Admin Panel (${API_URL%/api/v1}/admin/blog-posts):"
echo "  [ ] Can create post with 'Single Image' header style"
echo "  [ ] Can create post with 'Image Gallery' header style (upload 4+ images)"
echo "  [ ] Can create post with 'No Header Image' header style"
echo "  [ ] Can add Key Takeaways with different icons"
echo "  [ ] Gallery images can be reordered by drag"
echo "  [ ] Max 12 images enforced for gallery"
echo "  [ ] Max 8 takeaways enforced"
echo ""
echo "Frontend ($FRONTEND_URL/en/blog):"
echo "  [ ] Blog list page loads without errors"
echo "  [ ] Existing posts still display correctly"
echo "  [ ] Post with single image shows image header"
echo "  [ ] Post with gallery shows image grid"
echo "  [ ] Clicking gallery image opens lightbox"
echo "  [ ] Lightbox navigation (arrows, keyboard) works"
echo "  [ ] Post with 'none' header shows no image"
echo "  [ ] Key Takeaways section displays with icons"
echo "  [ ] French locale shows French takeaways"
echo ""

exit $FAILED
