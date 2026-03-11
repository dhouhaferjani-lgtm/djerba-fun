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
    """Generate a single horse and rider frame."""
    frame = create_frame()
    draw = aggdraw.Draw(frame)

    # Pens and brushes
    navy_pen = aggdraw.Pen(COLORS['navy'], 3)
    navy_pen_thick = aggdraw.Pen(COLORS['navy'], 6)
    navy_pen_medium = aggdraw.Pen(COLORS['navy'], 4)
    navy_brush = aggdraw.Brush(COLORS['navy'])
    green_brush = aggdraw.Brush(COLORS['green'])
    white_brush = aggdraw.Brush(COLORS['white'])
    orange_brush = aggdraw.Brush(COLORS['orange'])
    gray_brush = aggdraw.Brush(COLORS['gray'])
    gray_pen = aggdraw.Pen(COLORS['gray'], 3)

    # Animation parameters
    t = frame_num / total_frames
    phase = t * 2 * math.pi

    # Trot cycle
    leg_phase = frame_num / 5

    # Vertical bob
    bob_y = 4 * math.sin(phase * 2)

    # Tail swish
    tail_angle = 10 * math.sin(phase * 1.5)

    # Ground scroll
    ground_offset = (frame_num * 12) % 60

    # Arm wave (reduced for subtle movement)
    arm_wave = 8 * math.sin(phase)

    # Center positions
    horse_cx = 200
    horse_cy = 230 + bob_y  # Adjusted for new body shape

    # Horse body dimensions (2:1 ratio - longer, flatter)
    body_left = horse_cx - 80
    body_right = horse_cx + 80

    # --- Ground (dashed line) ---
    for i in range(-1, 8):
        dash_x = i * 60 - ground_offset
        if -30 < dash_x < WIDTH + 30:
            draw_smooth_line(draw, dash_x, 340, dash_x + 40, 340, gray_pen)

    # --- Dust particles ---
    if frame_num % 4 < 2:
        dust_offset = (frame_num % 8) * 4
        for i in range(3):
            dust_x = horse_cx - 80 - dust_offset + i * 8
            dust_y = 320 - dust_offset * 0.5 + bob_y + i * 3
            dust_r = max(2, 6 - dust_offset * 0.3)
            dust_alpha = max(50, 150 - dust_offset * 15)
            dust_color = f'#C8CDD3{dust_alpha:02x}'
            dust_brush = aggdraw.Brush(dust_color)
            draw_smooth_ellipse(draw, dust_x, dust_y, dust_r, dust_r, None, dust_brush)

    # --- Horse Tail ---
    tail_start_x = body_left + 5
    tail_start_y = horse_cy + 5

    # Draw tail as curved path
    tail_points = [
        (tail_start_x, tail_start_y),
        (tail_start_x - 25 + tail_angle, tail_start_y + 35),
        (tail_start_x - 35 + tail_angle * 0.7, tail_start_y + 60),
        (tail_start_x - 25 + tail_angle * 0.5, tail_start_y + 85),
    ]
    draw_bezier_path(draw, tail_points, navy_pen_medium)

    # Tail hair strands
    for offset in [-10, 0, 10]:
        strand_points = [
            (tail_start_x - 25 + tail_angle * 0.5, tail_start_y + 85),
            (tail_start_x - 30 + offset + tail_angle * 0.3, tail_start_y + 95 + abs(offset) * 0.3),
        ]
        draw_smooth_line(draw, strand_points[0][0], strand_points[0][1],
                        strand_points[1][0], strand_points[1][1], navy_pen)

    # --- Horse Back Legs (behind body) - Longer and thinner ---
    back_leg_x = horse_cx - 50

    for i, offset in enumerate([0, 18]):
        leg_swing = 18 * math.sin(leg_phase * math.pi * 2 + (i * math.pi))

        hip_x = back_leg_x + offset
        hip_y = horse_cy + 22
        knee_x = hip_x + leg_swing * 0.4
        knee_y = hip_y + 50  # Longer upper leg
        hoof_x = knee_x + leg_swing * 0.25
        hoof_y = knee_y + 50  # Longer lower leg

        # Upper leg (thinner)
        leg_pen = aggdraw.Pen(COLORS['navy'], 10)
        draw_smooth_line(draw, hip_x, hip_y, knee_x, knee_y, leg_pen)

        # Lower leg (thinner)
        leg_pen_lower = aggdraw.Pen(COLORS['navy'], 8)
        draw_smooth_line(draw, knee_x, knee_y, hoof_x, hoof_y, leg_pen_lower)

        # Hoof
        draw_smooth_ellipse(draw, hoof_x, hoof_y + 5, 7, 5, None, navy_brush)

    # --- Horse Body (realistic shape with flat back, curved belly) ---
    body_path = aggdraw.Path()
    # Start at chest (front-top)
    body_path.moveto(body_right - 5, horse_cy - 20)
    # Flat back (top edge) - slight rise at withers, then flat saddle area
    body_path.curveto(body_right - 30, horse_cy - 30,   # Slight rise at withers
                      horse_cx - 10, horse_cy - 28,      # Flat saddle area
                      body_left + 25, horse_cy - 18)     # Slope down to hindquarters
    # Hindquarters (back-top to back-bottom)
    body_path.curveto(body_left + 5, horse_cy - 10,
                      body_left - 5, horse_cy + 10,
                      body_left + 10, horse_cy + 25)
    # Belly (bottom edge) - curved downward
    body_path.curveto(body_left + 50, horse_cy + 35,     # Belly curves down
                      body_right - 50, horse_cy + 32,
                      body_right - 10, horse_cy + 18)
    # Chest (front-bottom to front-top) - broad and upright
    body_path.curveto(body_right + 8, horse_cy + 5,
                      body_right + 5, horse_cy - 10,
                      body_right - 5, horse_cy - 20)
    body_path.close()
    draw.path(body_path, navy_pen, green_brush)

    # --- Horse Front Legs - Longer and thinner ---
    front_leg_x = horse_cx + 45

    for i, offset in enumerate([0, 18]):
        leg_swing = 18 * math.sin(leg_phase * math.pi * 2 + math.pi + (i * math.pi))

        hip_x = front_leg_x + offset
        hip_y = horse_cy + 20
        knee_x = hip_x + leg_swing * 0.4
        knee_y = hip_y + 50  # Longer upper leg
        hoof_x = knee_x + leg_swing * 0.25
        hoof_y = knee_y + 50  # Longer lower leg

        # Upper leg (thinner)
        leg_pen = aggdraw.Pen(COLORS['navy'], 10)
        draw_smooth_line(draw, hip_x, hip_y, knee_x, knee_y, leg_pen)

        # Lower leg (thinner)
        leg_pen_lower = aggdraw.Pen(COLORS['navy'], 8)
        draw_smooth_line(draw, knee_x, knee_y, hoof_x, hoof_y, leg_pen_lower)

        # Hoof
        draw_smooth_ellipse(draw, hoof_x, hoof_y + 5, 7, 5, None, navy_brush)

    # --- Horse Neck (from chest at ~50° angle) ---
    neck_base_x = body_right - 10
    neck_base_y = horse_cy - 22
    neck_top_x = neck_base_x + 40
    neck_top_y = horse_cy - 90

    # Draw neck as filled polygon
    neck_path = aggdraw.Path()
    neck_path.moveto(neck_base_x - 15, neck_base_y + 5)
    neck_path.curveto(neck_base_x - 5, neck_base_y - 25,
                      neck_top_x - 25, neck_top_y + 20,
                      neck_top_x - 12, neck_top_y + 5)
    neck_path.lineto(neck_top_x + 10, neck_top_y + 10)
    neck_path.curveto(neck_top_x + 15, neck_top_y + 30,
                      neck_base_x + 20, neck_base_y - 5,
                      neck_base_x + 15, neck_base_y + 10)
    neck_path.close()
    draw.path(neck_path, navy_pen, green_brush)

    # --- Horse Mane ---
    for i in range(4):
        mane_t = 0.2 + i * 0.2
        mane_x = neck_base_x + (neck_top_x - neck_base_x) * mane_t - 15
        mane_y = neck_base_y + (neck_top_y - neck_base_y) * mane_t
        mane_wave = 4 * math.sin(phase + i * 0.5)
        draw_smooth_line(draw, mane_x, mane_y,
                        mane_x - 10 + mane_wave, mane_y + 18, navy_pen)

    # --- Horse Head (elongated snout shape) ---
    head_cx = neck_top_x + 32
    head_cy = neck_top_y + 18
    # More elongated head (snout shape)
    draw_smooth_ellipse(draw, head_cx, head_cy, 38, 15, navy_pen, green_brush)

    # Ear (pointing up)
    ear_path = aggdraw.Path()
    ear_path.moveto(head_cx - 18, head_cy - 8)
    ear_path.lineto(head_cx - 12, head_cy - 28)
    ear_path.lineto(head_cx - 2, head_cy - 6)
    ear_path.close()
    ear_pen = aggdraw.Pen(COLORS['navy'], 2)
    draw.path(ear_path, ear_pen, green_brush)

    # Eye
    draw_smooth_ellipse(draw, head_cx - 5, head_cy - 2, 4, 4, None, navy_brush)

    # Nostril (at front of snout)
    draw_smooth_ellipse(draw, head_cx + 32, head_cy + 4, 3, 3, None, navy_brush)

    # --- Rider (centered on horse's back) ---
    rider_x = horse_cx - 5  # Centered on flat back area
    rider_y = horse_cy - 68  # Adjusted for new body shape

    # Rider torso (upright)
    torso_path = aggdraw.Path()
    torso_path.moveto(rider_x - 12, rider_y + 12)
    torso_path.lineto(rider_x - 10, rider_y + 45)
    torso_path.lineto(rider_x + 10, rider_y + 45)
    torso_path.lineto(rider_x + 12, rider_y + 12)
    torso_path.close()
    draw.path(torso_path, None, navy_brush)

    # Rider legs (hanging naturally with stirrups)
    rider_leg_pen = aggdraw.Pen(COLORS['navy'], 7)
    stirrup_pen = aggdraw.Pen(COLORS['navy'], 3)
    # Left leg - hanging down left side
    draw_smooth_line(draw, rider_x - 6, rider_y + 43,
                    rider_x - 22, rider_y + 78, rider_leg_pen)
    # Left stirrup
    draw_smooth_line(draw, rider_x - 25, rider_y + 78,
                    rider_x - 19, rider_y + 78, stirrup_pen)

    # Right leg - hanging down right side
    draw_smooth_line(draw, rider_x + 6, rider_y + 43,
                    rider_x + 22, rider_y + 78, rider_leg_pen)
    # Right stirrup
    draw_smooth_line(draw, rider_x + 19, rider_y + 78,
                    rider_x + 25, rider_y + 78, stirrup_pen)

    # Rider left arm (holding reins - forward and down)
    arm_pen = aggdraw.Pen(COLORS['navy'], 6)
    rein_hand_x = rider_x + 50
    rein_hand_y = rider_y + 32
    draw_smooth_line(draw, rider_x + 8, rider_y + 18,
                    rein_hand_x, rein_hand_y, arm_pen)
    # Reins (thin line to horse head)
    rein_pen = aggdraw.Pen(COLORS['navy'], 2)
    draw_smooth_line(draw, rein_hand_x, rein_hand_y,
                    head_cx - 15, head_cy + 10, rein_pen)

    # Rider right arm (at side with subtle wave)
    arm_end_x = rider_x - 28
    arm_end_y = rider_y + 38 - arm_wave  # Mostly down, slight wave
    draw_smooth_line(draw, rider_x - 8, rider_y + 18,
                    arm_end_x, arm_end_y, arm_pen)

    # Rider head
    draw_smooth_ellipse(draw, rider_x, rider_y, 16, 16, aggdraw.Pen(COLORS['navy'], 2.5), white_brush)

    # Face - eyes
    draw_smooth_ellipse(draw, rider_x - 5, rider_y - 2, 2, 2, None, navy_brush)
    draw_smooth_ellipse(draw, rider_x + 5, rider_y - 2, 2, 2, None, navy_brush)

    # Face - smile
    smile_pen = aggdraw.Pen(COLORS['navy'], 2)
    smile_path = aggdraw.Path()
    smile_path.moveto(rider_x - 6, rider_y + 5)
    smile_path.curveto(rider_x - 3, rider_y + 10,
                       rider_x + 3, rider_y + 10,
                       rider_x + 6, rider_y + 5)
    draw.path(smile_path, smile_pen)

    # Rider hat/cap
    hat_pen = aggdraw.Pen(COLORS['navy'], 1.5)
    draw_smooth_ellipse(draw, rider_x, rider_y - 14, 14, 6, hat_pen, orange_brush)
    hat_top = aggdraw.Path()
    hat_top.moveto(rider_x - 12, rider_y - 14)
    hat_top.curveto(rider_x - 12, rider_y - 25,
                    rider_x + 12, rider_y - 25,
                    rider_x + 12, rider_y - 14)
    draw.path(hat_top, hat_pen, orange_brush)

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
