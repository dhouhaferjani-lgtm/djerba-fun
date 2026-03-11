#!/usr/bin/env python3
"""
Generate animated GIF icons using aggdraw for smooth anti-aliased vector-quality curves.

aggdraw provides anti-aliased drawing with bezier curves support for Pillow,
producing far superior results to PIL's primitive drawing tools.

Usage: python3 scripts/generate-service-gifs-svg.py [activites|nautique|all]
"""

import math
import os
import random
import sys
from PIL import Image

try:
    import aggdraw
except ImportError:
    print("ERROR: aggdraw not installed. Run: pip install aggdraw")
    sys.exit(1)

# ============================================================================
# Configuration
# ============================================================================

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), '..', 'apps', 'web', 'public', 'images', 'experiences')

WIDTH = 400
HEIGHT = 400
FRAME_DURATION = 70  # milliseconds
TOTAL_FRAMES = 20

# Color palette
COLORS = {
    'navy': '#1E2D4F',
    'green': '#2A7D5B',
    'orange': '#E8920D',
    'white': '#FFFFFF',
    'gray': '#C8CDD3',
    'light_blue': '#87CEEB',
    # Horse colors (for realistic style)
    'horse_body': '#9A8B7A',      # Taupe/grayish-brown
    'horse_dark': '#6B5D4D',      # Darker brown for mane/tail
    'horse_outline': '#5A4D3F',   # Dark outline
    'rider_body': '#F5F5F5',      # Off-white rider
    'rider_outline': '#B0A090',   # Light taupe outline for rider
}


def create_frame():
    """Create a transparent frame."""
    return Image.new('RGBA', (WIDTH, HEIGHT), (0, 0, 0, 0))


def save_gif(frames, filename, duration=FRAME_DURATION):
    """Save frames as an animated GIF."""
    filepath = os.path.join(OUTPUT_DIR, filename)

    frames[0].save(
        filepath,
        save_all=True,
        append_images=frames[1:],
        duration=duration,
        loop=0,
        disposal=2,
        optimize=True
    )
    file_size = os.path.getsize(filepath)
    print(f"  Saved: {filename} ({file_size / 1024:.1f} KB)")
    return filepath


def hex_to_rgb(hex_color):
    """Convert hex color to RGB tuple."""
    hex_color = hex_color.lstrip('#')
    return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))


# ============================================================================
# aggdraw helpers for smooth bezier curves
# ============================================================================

def draw_bezier_path(draw, points, pen=None, brush=None):
    """Draw a path using cubic bezier curves through points.

    points: list of (x, y) tuples
    Uses Path object with curveto commands for smooth curves.
    """
    if len(points) < 2:
        return

    path = aggdraw.Path()
    path.moveto(points[0][0], points[0][1])

    if len(points) == 2:
        # Simple line
        path.lineto(points[1][0], points[1][1])
    else:
        # Use quadratic bezier for smoother curves
        for i in range(1, len(points) - 1):
            # Control point is the current point
            # End point is midpoint to next point
            cx, cy = points[i]
            nx, ny = points[i + 1]
            ex, ey = (cx + nx) / 2, (cy + ny) / 2
            path.curveto(cx, cy, cx, cy, ex, ey)
        # Last segment to final point
        path.lineto(points[-1][0], points[-1][1])

    if brush:
        draw.path(path, pen, brush)
    else:
        draw.path(path, pen)


def draw_cubic_bezier(draw, x1, y1, cx1, cy1, cx2, cy2, x2, y2, pen):
    """Draw a cubic bezier curve."""
    path = aggdraw.Path()
    path.moveto(x1, y1)
    path.curveto(cx1, cy1, cx2, cy2, x2, y2)
    draw.path(path, pen)


def draw_smooth_ellipse(draw, cx, cy, rx, ry, pen=None, brush=None):
    """Draw an anti-aliased ellipse."""
    draw.ellipse((cx - rx, cy - ry, cx + rx, cy + ry), pen, brush)


def draw_smooth_line(draw, x1, y1, x2, y2, pen):
    """Draw an anti-aliased line."""
    draw.line((x1, y1, x2, y2), pen)


# ============================================================================
# GIF 1: ACTIVITÉS - Horse & Rider
# ============================================================================

def generate_horse_frame(frame_num, total_frames):
    """Generate a single horse and rider frame - realistic minimalist style."""
    frame = create_frame()
    draw = aggdraw.Draw(frame)

    # Pens and brushes - realistic horse colors
    horse_brush = aggdraw.Brush(COLORS['horse_body'])
    horse_pen = aggdraw.Pen(COLORS['horse_outline'], 2)
    dark_brush = aggdraw.Brush(COLORS['horse_dark'])
    dark_pen = aggdraw.Pen(COLORS['horse_dark'], 3)
    hoof_brush = aggdraw.Brush('#2A2420')  # Very dark brown/black hooves
    rider_brush = aggdraw.Brush(COLORS['rider_body'])
    rider_pen = aggdraw.Pen(COLORS['rider_outline'], 1.5)
    rein_pen = aggdraw.Pen(COLORS['horse_dark'], 1.5)

    # Animation parameters
    t = frame_num / total_frames
    phase = t * 2 * math.pi

    # Trot cycle - smoother gallop
    leg_phase = frame_num / 4  # Slightly faster cycle

    # Vertical bob (subtle)
    bob_y = 3 * math.sin(phase * 2)

    # Tail swish
    tail_wave = 8 * math.sin(phase * 1.2)

    # Mane wave
    mane_wave = 5 * math.sin(phase * 1.5)

    # Center positions - horse positioned in frame
    horse_cx = 200
    horse_cy = 210 + bob_y

    # =========================================================================
    # HORSE TAIL (behind everything)
    # =========================================================================
    tail_base_x = horse_cx - 75
    tail_base_y = horse_cy + 5

    # Flowing tail with multiple strands
    tail_pen = aggdraw.Pen(COLORS['horse_dark'], 4)
    for i in range(5):
        offset = (i - 2) * 6
        wave = tail_wave + i * 2
        tail_path = aggdraw.Path()
        tail_path.moveto(tail_base_x, tail_base_y + offset * 0.3)
        tail_path.curveto(
            tail_base_x - 30 + wave, tail_base_y + 25 + offset,
            tail_base_x - 45 + wave * 0.8, tail_base_y + 55 + offset,
            tail_base_x - 35 + wave * 0.5, tail_base_y + 80 + offset
        )
        draw.path(tail_path, tail_pen)

    # =========================================================================
    # HORSE BACK LEGS (behind body)
    # =========================================================================
    # Back legs - realistic trot gait
    for leg_idx in range(2):
        # Offset for front/back leg of the pair
        leg_offset = leg_idx * 12
        # Phase offset for diagonal gait
        swing = 22 * math.sin(leg_phase * math.pi * 2 + leg_idx * math.pi)

        # Hip position
        hip_x = horse_cx - 45 + leg_offset
        hip_y = horse_cy + 25

        # Upper leg (thigh)
        upper_angle = math.radians(-10 + swing)
        upper_len = 45
        knee_x = hip_x + upper_len * math.sin(upper_angle)
        knee_y = hip_y + upper_len * math.cos(upper_angle)

        # Lower leg (cannon)
        lower_angle = math.radians(5 + swing * 0.6)
        lower_len = 50
        hoof_x = knee_x + lower_len * math.sin(lower_angle)
        hoof_y = knee_y + lower_len * math.cos(lower_angle)

        # Draw leg segments
        upper_pen = aggdraw.Pen(COLORS['horse_body'], 14)
        lower_pen = aggdraw.Pen(COLORS['horse_body'], 10)

        draw_smooth_line(draw, hip_x, hip_y, knee_x, knee_y, upper_pen)
        draw_smooth_line(draw, knee_x, knee_y, hoof_x, hoof_y, lower_pen)

        # Hoof
        draw_smooth_ellipse(draw, hoof_x, hoof_y + 3, 6, 4, None, hoof_brush)

    # =========================================================================
    # HORSE BODY - Realistic shape
    # =========================================================================
    body_path = aggdraw.Path()

    # Dimensions
    body_left = horse_cx - 78
    body_right = horse_cx + 72

    # Start at chest (front upper)
    body_path.moveto(body_right, horse_cy - 15)

    # Back line - withers rise, then saddle dip, then croup
    body_path.curveto(
        body_right - 25, horse_cy - 28,   # Withers (high point)
        horse_cx - 20, horse_cy - 25,      # Saddle area (slight dip)
        body_left + 30, horse_cy - 18      # Croup starts
    )

    # Hindquarters - round curve down
    body_path.curveto(
        body_left + 5, horse_cy - 8,
        body_left - 5, horse_cy + 15,
        body_left + 15, horse_cy + 30
    )

    # Belly - curves under
    body_path.curveto(
        body_left + 45, horse_cy + 40,
        body_right - 45, horse_cy + 38,
        body_right - 5, horse_cy + 25
    )

    # Chest - broad, curves up to start
    body_path.curveto(
        body_right + 10, horse_cy + 10,
        body_right + 8, horse_cy - 5,
        body_right, horse_cy - 15
    )

    body_path.close()
    draw.path(body_path, horse_pen, horse_brush)

    # =========================================================================
    # HORSE FRONT LEGS
    # =========================================================================
    for leg_idx in range(2):
        leg_offset = leg_idx * 15
        # Opposite phase from back legs (diagonal gait)
        swing = 22 * math.sin(leg_phase * math.pi * 2 + math.pi + leg_idx * math.pi)

        # Shoulder position
        hip_x = horse_cx + 40 + leg_offset
        hip_y = horse_cy + 22

        # Upper leg
        upper_angle = math.radians(-8 + swing)
        upper_len = 45
        knee_x = hip_x + upper_len * math.sin(upper_angle)
        knee_y = hip_y + upper_len * math.cos(upper_angle)

        # Lower leg
        lower_angle = math.radians(3 + swing * 0.5)
        lower_len = 50
        hoof_x = knee_x + lower_len * math.sin(lower_angle)
        hoof_y = knee_y + lower_len * math.cos(lower_angle)

        # Draw leg segments
        upper_pen = aggdraw.Pen(COLORS['horse_body'], 12)
        lower_pen = aggdraw.Pen(COLORS['horse_body'], 9)

        draw_smooth_line(draw, hip_x, hip_y, knee_x, knee_y, upper_pen)
        draw_smooth_line(draw, knee_x, knee_y, hoof_x, hoof_y, lower_pen)

        # Hoof
        draw_smooth_ellipse(draw, hoof_x, hoof_y + 3, 6, 4, None, hoof_brush)

    # =========================================================================
    # HORSE NECK - Elegant curve
    # =========================================================================
    neck_base_x = body_right - 5
    neck_base_y = horse_cy - 18
    neck_top_x = neck_base_x + 45
    neck_top_y = horse_cy - 85

    neck_path = aggdraw.Path()
    # Back of neck (left side)
    neck_path.moveto(neck_base_x - 20, neck_base_y + 5)
    neck_path.curveto(
        neck_base_x - 10, neck_base_y - 30,
        neck_top_x - 30, neck_top_y + 25,
        neck_top_x - 15, neck_top_y
    )
    # Front of neck (connects to head area)
    neck_path.lineto(neck_top_x + 8, neck_top_y + 8)
    # Front of neck (right side)
    neck_path.curveto(
        neck_top_x + 15, neck_top_y + 35,
        neck_base_x + 20, neck_base_y - 10,
        neck_base_x + 15, neck_base_y + 10
    )
    neck_path.close()
    draw.path(neck_path, horse_pen, horse_brush)

    # =========================================================================
    # HORSE MANE - Flowing dark mane
    # =========================================================================
    mane_pen = aggdraw.Pen(COLORS['horse_dark'], 5)
    for i in range(7):
        mane_t = 0.1 + i * 0.12
        # Position along neck
        mx = neck_base_x - 15 + (neck_top_x - 15 - neck_base_x + 15) * mane_t
        my = neck_base_y + 5 + (neck_top_y - neck_base_y - 5) * mane_t
        # Wave animation
        wave = mane_wave * (1 - mane_t * 0.5)
        # Draw mane strand
        mane_path = aggdraw.Path()
        mane_path.moveto(mx, my)
        mane_path.curveto(
            mx - 12 + wave, my + 10,
            mx - 18 + wave * 0.7, my + 20,
            mx - 15 + wave * 0.5, my + 28
        )
        draw.path(mane_path, mane_pen)

    # =========================================================================
    # HORSE HEAD - Elegant elongated shape
    # =========================================================================
    head_cx = neck_top_x + 35
    head_cy = neck_top_y + 15

    # Head shape - elongated, slightly downward pointing
    head_path = aggdraw.Path()
    head_path.moveto(head_cx - 30, head_cy - 8)  # Back of head
    # Top of head/forehead
    head_path.curveto(
        head_cx - 20, head_cy - 18,
        head_cx + 10, head_cy - 15,
        head_cx + 35, head_cy - 5
    )
    # Nose/muzzle
    head_path.curveto(
        head_cx + 42, head_cy + 2,
        head_cx + 40, head_cy + 12,
        head_cx + 32, head_cy + 15
    )
    # Bottom of head/jaw
    head_path.curveto(
        head_cx + 15, head_cy + 18,
        head_cx - 10, head_cy + 15,
        head_cx - 30, head_cy + 5
    )
    head_path.close()
    draw.path(head_path, horse_pen, horse_brush)

    # Ear
    ear_path = aggdraw.Path()
    ear_path.moveto(head_cx - 22, head_cy - 10)
    ear_path.curveto(
        head_cx - 25, head_cy - 25,
        head_cx - 15, head_cy - 30,
        head_cx - 12, head_cy - 15
    )
    ear_path.close()
    draw.path(ear_path, horse_pen, horse_brush)

    # Eye - simple dark dot
    draw_smooth_ellipse(draw, head_cx - 5, head_cy - 3, 3, 3, None, dark_brush)

    # Nostril
    draw_smooth_ellipse(draw, head_cx + 32, head_cy + 8, 3, 2, None, dark_brush)

    # =========================================================================
    # RIDER - Simple minimalist white silhouette
    # =========================================================================
    rider_x = horse_cx + 5
    rider_y = horse_cy - 70

    # Rider body (simple shape)
    rider_body_path = aggdraw.Path()
    # Torso - upright
    rider_body_path.moveto(rider_x - 10, rider_y + 10)
    rider_body_path.lineto(rider_x - 8, rider_y + 40)
    rider_body_path.lineto(rider_x + 8, rider_y + 40)
    rider_body_path.lineto(rider_x + 10, rider_y + 10)
    rider_body_path.close()
    draw.path(rider_body_path, rider_pen, rider_brush)

    # Rider head
    draw_smooth_ellipse(draw, rider_x, rider_y - 2, 12, 13, rider_pen, rider_brush)

    # Rider arms - both forward holding reins
    arm_pen = aggdraw.Pen(COLORS['rider_body'], 6)
    arm_outline_pen = aggdraw.Pen(COLORS['rider_outline'], 1)

    # Right arm (forward to reins)
    arm_path_r = aggdraw.Path()
    arm_path_r.moveto(rider_x + 6, rider_y + 15)
    arm_path_r.curveto(
        rider_x + 25, rider_y + 20,
        rider_x + 40, rider_y + 30,
        rider_x + 50, rider_y + 35
    )
    draw.path(arm_path_r, arm_pen)

    # Left arm (forward to reins, slightly behind)
    arm_path_l = aggdraw.Path()
    arm_path_l.moveto(rider_x - 2, rider_y + 15)
    arm_path_l.curveto(
        rider_x + 15, rider_y + 22,
        rider_x + 35, rider_y + 32,
        rider_x + 48, rider_y + 38
    )
    draw.path(arm_path_l, arm_pen)

    # Reins - thin lines from hands to horse head
    draw_smooth_line(draw, rider_x + 50, rider_y + 35,
                    head_cx - 10, head_cy + 12, rein_pen)
    draw_smooth_line(draw, rider_x + 48, rider_y + 38,
                    head_cx - 8, head_cy + 14, rein_pen)

    # Rider legs - hanging on sides of horse
    leg_pen = aggdraw.Pen(COLORS['rider_body'], 7)

    # Right leg
    leg_path_r = aggdraw.Path()
    leg_path_r.moveto(rider_x + 5, rider_y + 38)
    leg_path_r.curveto(
        rider_x + 15, rider_y + 50,
        rider_x + 22, rider_y + 65,
        rider_x + 20, rider_y + 80
    )
    draw.path(leg_path_r, leg_pen)

    # Left leg
    leg_path_l = aggdraw.Path()
    leg_path_l.moveto(rider_x - 5, rider_y + 38)
    leg_path_l.curveto(
        rider_x - 15, rider_y + 50,
        rider_x - 22, rider_y + 65,
        rider_x - 20, rider_y + 80
    )
    draw.path(leg_path_l, leg_pen)

    draw.flush()
    return frame


def generate_activites_gif():
    """Generate activites.gif using aggdraw."""
    print("Generating activites.gif (aggdraw)...")
    frames = []

    for i in range(TOTAL_FRAMES):
        frame = generate_horse_frame(i, TOTAL_FRAMES)
        frames.append(frame)

    return save_gif(frames, 'activites.gif')


# ============================================================================
# GIF 2: NAUTIQUE - Jet Ski with Rider
# ============================================================================

def generate_jetski_frame(frame_num, total_frames):
    """Generate a single jet ski and rider frame."""
    frame = create_frame()
    draw = aggdraw.Draw(frame)

    # Pens and brushes
    navy_pen = aggdraw.Pen(COLORS['navy'], 3)
    navy_pen_thick = aggdraw.Pen(COLORS['navy'], 4)
    navy_pen_medium = aggdraw.Pen(COLORS['navy'], 2)
    navy_brush = aggdraw.Brush(COLORS['navy'])
    green_brush = aggdraw.Brush(COLORS['green'])
    white_brush = aggdraw.Brush(COLORS['white'])
    orange_brush = aggdraw.Brush(COLORS['orange'])

    # Animation parameters
    t = frame_num / total_frames
    phase = t * 2 * math.pi

    # Vertical bounce
    bounce_y = 6 * math.sin(phase * 2)

    # Rotation tilt
    tilt = 3 * math.sin(phase * 2)

    # Wave scroll
    wave_offset = frame_num * 15

    # Scarf flutter variant
    scarf_variant = frame_num % 3

    # Center positions
    jetski_cx = 200
    jetski_cy = 200 + bounce_y

    # --- Water waves ---
    for wave_idx, (wave_y_base, amplitude, stroke_width) in enumerate([
        (300, 12, 3), (330, 10, 2.5), (360, 8, 2)
    ]):
        wave_pen = aggdraw.Pen(COLORS['navy'], stroke_width)
        wave_path = aggdraw.Path()
        phase_offset = wave_idx * 0.8

        # First point
        wy = wave_y_base + amplitude * math.sin(2 * math.pi * (-wave_offset) / 80 + phase_offset)
        wave_path.moveto(-20, wy)

        # Draw wave using curves
        for wx in range(0, WIDTH + 80, 40):
            adjusted_x = wx - wave_offset
            wy1 = wave_y_base + amplitude * math.sin(2 * math.pi * adjusted_x / 80 + phase_offset)
            wy2 = wave_y_base + amplitude * math.sin(2 * math.pi * (adjusted_x + 40) / 80 + phase_offset)
            wave_path.curveto(wx, wy1, wx + 20, (wy1 + wy2) / 2, wx + 40, wy2)

        draw.path(wave_path, wave_pen)

    # --- Splash particles ---
    splash_x = jetski_cx - 120
    splash_y = jetski_cy + 20
    num_particles = 10 if frame_num % 10 < 3 else 6

    for i in range(num_particles):
        angle = math.radians(-110 - i * 10 + random.randint(-3, 3))
        dist = 25 + i * 10 + (8 if frame_num % 10 < 3 else 0)
        px = splash_x + dist * math.cos(angle)
        py = splash_y + dist * math.sin(angle)
        size = max(2, 7 - i * 0.6 + (2 if frame_num % 10 < 3 else 0))
        alpha = max(100, 230 - i * 20)
        white_alpha = f'#FFFFFF{alpha:02x}'
        splash_brush = aggdraw.Brush(white_alpha)
        draw_smooth_ellipse(draw, px, py, size, size, None, splash_brush)

    # Additional spray
    for _ in range(4):
        px = splash_x + random.randint(-40, -10)
        py = splash_y + random.randint(-30, 15)
        size = random.uniform(2, 4)
        spray_brush = aggdraw.Brush('#87CEEBB0')
        draw_smooth_ellipse(draw, px, py, size, size, None, spray_brush)

    # --- Jet Ski Hull ---
    # Apply tilt by adjusting y coordinates
    def tilt_y(y, x):
        return y + (x - jetski_cx) * math.sin(math.radians(tilt)) * 0.05

    # Hull using cubic bezier path
    hull_path = aggdraw.Path()

    # Start at front bow
    hull_path.moveto(jetski_cx + 95, tilt_y(jetski_cy - 18, jetski_cx + 95))

    # Top curve from bow to stern
    hull_path.curveto(
        jetski_cx + 100, tilt_y(jetski_cy - 30, jetski_cx + 100),
        jetski_cx + 70, tilt_y(jetski_cy - 45, jetski_cx + 70),
        jetski_cx + 30, tilt_y(jetski_cy - 48, jetski_cx + 30)
    )
    hull_path.curveto(
        jetski_cx - 20, tilt_y(jetski_cy - 50, jetski_cx - 20),
        jetski_cx - 70, tilt_y(jetski_cy - 45, jetski_cx - 70),
        jetski_cx - 100, tilt_y(jetski_cy - 30, jetski_cx - 100)
    )

    # Stern
    hull_path.lineto(jetski_cx - 110, tilt_y(jetski_cy + 5, jetski_cx - 110))

    # Bottom curve
    hull_path.curveto(
        jetski_cx - 100, tilt_y(jetski_cy + 20, jetski_cx - 100),
        jetski_cx - 50, tilt_y(jetski_cy + 25, jetski_cx - 50),
        jetski_cx, tilt_y(jetski_cy + 22, jetski_cx)
    )
    hull_path.curveto(
        jetski_cx + 50, tilt_y(jetski_cy + 18, jetski_cx + 50),
        jetski_cx + 80, tilt_y(jetski_cy + 8, jetski_cx + 80),
        jetski_cx + 95, tilt_y(jetski_cy - 18, jetski_cx + 95)
    )
    hull_path.close()

    draw.path(hull_path, navy_pen, green_brush)

    # Seat
    seat_path = aggdraw.Path()
    seat_left = jetski_cx - 35
    seat_right = jetski_cx + 25
    seat_path.moveto(seat_left, tilt_y(jetski_cy - 45, seat_left))
    seat_path.curveto(
        seat_left + 10, tilt_y(jetski_cy - 60, seat_left + 10),
        seat_right - 10, tilt_y(jetski_cy - 60, seat_right - 10),
        seat_right, tilt_y(jetski_cy - 48, seat_right)
    )
    seat_path.lineto(seat_right - 5, tilt_y(jetski_cy - 42, seat_right - 5))
    seat_path.curveto(
        seat_right - 15, tilt_y(jetski_cy - 52, seat_right - 15),
        seat_left + 15, tilt_y(jetski_cy - 52, seat_left + 15),
        seat_left + 5, tilt_y(jetski_cy - 45, seat_left + 5)
    )
    seat_path.close()
    seat_pen = aggdraw.Pen(COLORS['navy'], 2)
    draw.path(seat_path, seat_pen, green_brush)

    # Handlebars
    hbar_x = jetski_cx + 50
    hbar_y = tilt_y(jetski_cy - 55, hbar_x)
    hbar_pen = aggdraw.Pen(COLORS['navy'], 4)
    draw_smooth_line(draw, hbar_x, tilt_y(jetski_cy - 45, hbar_x), hbar_x + 5, hbar_y, hbar_pen)
    draw_smooth_line(draw, hbar_x - 12, hbar_y - 2, hbar_x + 18, hbar_y + 2, hbar_pen)
    # Grips
    draw_smooth_ellipse(draw, hbar_x - 14, hbar_y - 2, 5, 5, None, navy_brush)
    draw_smooth_ellipse(draw, hbar_x + 20, hbar_y + 2, 5, 5, None, navy_brush)

    # Exhaust
    exhaust_pen = aggdraw.Pen(COLORS['navy'], 1)
    exhaust_x = jetski_cx - 112
    draw.rectangle((exhaust_x, tilt_y(jetski_cy - 2, exhaust_x),
                   exhaust_x + 10, tilt_y(jetski_cy + 6, exhaust_x + 10)),
                  exhaust_pen, navy_brush)

    # --- Rider ---
    rider_x = jetski_cx - 10
    rider_y = jetski_cy - 100

    # Scarf/bandana
    scarf_base_x = rider_x - 14
    scarf_base_y = rider_y + 5

    scarf_pen = aggdraw.Pen(COLORS['orange'], 6)
    scarf_paths = [
        # Variant 0
        [(scarf_base_x, scarf_base_y), (scarf_base_x - 30, scarf_base_y - 5), (scarf_base_x - 50, scarf_base_y + 5)],
        # Variant 1
        [(scarf_base_x, scarf_base_y), (scarf_base_x - 35, scarf_base_y + 5), (scarf_base_x - 55, scarf_base_y - 3)],
        # Variant 2
        [(scarf_base_x, scarf_base_y), (scarf_base_x - 28, scarf_base_y - 8), (scarf_base_x - 48, scarf_base_y + 2)],
    ]

    for scarf_points in [scarf_paths[scarf_variant]]:
        draw_bezier_path(draw, scarf_points, scarf_pen)

    # Second scarf line
    scarf_pen2 = aggdraw.Pen(COLORS['orange'], 5)
    scarf_paths2 = [
        [(scarf_base_x, scarf_base_y + 8), (scarf_base_x - 25, scarf_base_y + 10), (scarf_base_x - 45, scarf_base_y + 15)],
        [(scarf_base_x, scarf_base_y + 8), (scarf_base_x - 30, scarf_base_y + 5), (scarf_base_x - 50, scarf_base_y + 10)],
        [(scarf_base_x, scarf_base_y + 8), (scarf_base_x - 32, scarf_base_y + 15), (scarf_base_x - 52, scarf_base_y + 8)],
    ]
    draw_bezier_path(draw, scarf_paths2[scarf_variant], scarf_pen2)

    # Body (leaning forward)
    body_path = aggdraw.Path()
    body_path.moveto(rider_x - 8, rider_y + 18)
    body_path.lineto(rider_x + 15, rider_y + 55)
    body_path.lineto(rider_x + 25, rider_y + 52)
    body_path.lineto(rider_x + 8, rider_y + 16)
    body_path.close()
    draw.path(body_path, None, navy_brush)

    # Arms to handlebars
    arm_pen = aggdraw.Pen(COLORS['navy'], 7)
    # Left arm
    grip_left_x = hbar_x - 12
    grip_left_y = hbar_y - 2
    arm_path_l = aggdraw.Path()
    arm_path_l.moveto(rider_x - 2, rider_y + 22)
    arm_path_l.curveto(rider_x + 20, rider_y + 30,
                       grip_left_x - 10, grip_left_y + 10,
                       grip_left_x, grip_left_y)
    draw.path(arm_path_l, arm_pen)

    # Right arm
    grip_right_x = hbar_x + 18
    grip_right_y = hbar_y + 2
    arm_path_r = aggdraw.Path()
    arm_path_r.moveto(rider_x + 5, rider_y + 22)
    arm_path_r.curveto(rider_x + 35, rider_y + 25,
                       grip_right_x - 10, grip_right_y + 10,
                       grip_right_x, grip_right_y)
    draw.path(arm_path_r, arm_pen)

    # Legs
    leg_pen = aggdraw.Pen(COLORS['navy'], 8)
    # Left leg
    leg_path_l = aggdraw.Path()
    leg_path_l.moveto(rider_x + 12, rider_y + 53)
    leg_path_l.curveto(rider_x - 5, rider_y + 70,
                       rider_x - 20, rider_y + 80,
                       rider_x - 25, rider_y + 88)
    draw.path(leg_path_l, leg_pen)

    # Right leg
    leg_path_r = aggdraw.Path()
    leg_path_r.moveto(rider_x + 22, rider_y + 50)
    leg_path_r.curveto(rider_x + 40, rider_y + 65,
                       rider_x + 48, rider_y + 78,
                       rider_x + 50, rider_y + 85)
    draw.path(leg_path_r, leg_pen)

    # Head
    draw_smooth_ellipse(draw, rider_x, rider_y, 16, 16,
                       aggdraw.Pen(COLORS['navy'], 2.5), white_brush)

    # Sunglasses
    glasses_brush = aggdraw.Brush(COLORS['navy'])
    draw.rectangle((rider_x - 12, rider_y - 5, rider_x - 2, rider_y + 1), None, glasses_brush)
    draw.rectangle((rider_x + 2, rider_y - 5, rider_x + 12, rider_y + 1), None, glasses_brush)
    glasses_pen = aggdraw.Pen(COLORS['navy'], 2)
    draw_smooth_line(draw, rider_x - 2, rider_y - 2, rider_x + 2, rider_y - 2, glasses_pen)

    # Big smile
    smile_pen = aggdraw.Pen(COLORS['navy'], 2.5)
    smile_path = aggdraw.Path()
    smile_path.moveto(rider_x - 8, rider_y + 6)
    smile_path.curveto(rider_x - 4, rider_y + 14,
                       rider_x + 4, rider_y + 14,
                       rider_x + 8, rider_y + 6)
    draw.path(smile_path, smile_pen)

    draw.flush()
    return frame


def generate_nautique_gif():
    """Generate nautique.gif using aggdraw."""
    print("Generating nautique.gif (aggdraw)...")
    frames = []

    for i in range(TOTAL_FRAMES):
        frame = generate_jetski_frame(i, TOTAL_FRAMES)
        frames.append(frame)

    return save_gif(frames, 'nautique.gif')


# ============================================================================
# Main
# ============================================================================

def main():
    """Generate GIF files using aggdraw for smooth anti-aliased curves."""
    print("=" * 60)
    print("Generating Service Type GIFs using aggdraw")
    print("=" * 60)
    print(f"Output directory: {OUTPUT_DIR}")
    print(f"Frame duration: {FRAME_DURATION}ms")
    print(f"Canvas size: {WIDTH}x{HEIGHT}px")
    print(f"Total frames: {TOTAL_FRAMES}")
    print()

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    args = sys.argv[1:] if len(sys.argv) > 1 else ['all']

    if 'all' in args:
        generate_activites_gif()
        generate_nautique_gif()
    else:
        if 'activites' in args or 'horse' in args:
            generate_activites_gif()
        if 'nautique' in args or 'jetski' in args:
            generate_nautique_gif()

    print()
    print("=" * 60)
    print("aggdraw-based GIF generation complete!")
    print("=" * 60)


if __name__ == '__main__':
    main()
