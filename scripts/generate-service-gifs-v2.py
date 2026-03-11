#!/usr/bin/env python3
"""
Generate animated GIF icons with stick-figure humans for Djerba Fun.

This script creates 4 animated GIFs with human characters:
- activites.gif: Horse & rider trotting
- nautique.gif: Jet ski with rider bouncing on waves
- hebergements.gif: Person walking to house, door opens
- evenements.gif: Group celebration with campfire

Usage: python3 scripts/generate-service-gifs-v2.py
"""

import math
import os
import random
from PIL import Image, ImageDraw

# ============================================================================
# Configuration
# ============================================================================

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), '..', 'apps', 'web', 'public', 'images', 'experiences')

# Standard resolution
WIDTH = 400
HEIGHT = 400
FRAME_DURATION = 70  # milliseconds
TOTAL_FRAMES = 24
STROKE_WIDTH = 3

# Hi-res settings for activites and nautique (draw at 2x, resize down with LANCZOS)
HIRES_SIZE = 800
OUTPUT_SIZE = 400
SCALE = 2  # HIRES_SIZE / OUTPUT_SIZE

# Color palette
COLORS = {
    'navy': (30, 45, 79),           # #1E2D4F - outlines, humans
    'green': (42, 125, 91),         # #2A7D5B - fills
    'orange': (232, 146, 13),       # #E8920D - accents
    'white': (255, 255, 255),       # #FFFFFF
    'gray': (200, 205, 211),        # #C8CDD3
    'transparent': (0, 0, 0, 0),
}


# ============================================================================
# Helper Functions
# ============================================================================

def create_frame():
    """Create a new transparent frame."""
    return Image.new('RGBA', (WIDTH, HEIGHT), COLORS['transparent'])


def create_hires_frame():
    """Create a hi-res transparent frame for better quality."""
    return Image.new('RGBA', (HIRES_SIZE, HIRES_SIZE), COLORS['transparent'])


def resize_frame(frame):
    """Resize hi-res frame to output size with LANCZOS for crisp anti-aliasing."""
    return frame.resize((OUTPUT_SIZE, OUTPUT_SIZE), Image.LANCZOS)


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
        transparency=0,
        optimize=True
    )
    file_size = os.path.getsize(filepath)
    print(f"  Saved: {filename} ({file_size / 1024:.1f} KB)")
    return filepath


# ============================================================================
# Stick Figure Drawing Functions
# ============================================================================

def draw_stick_head(draw, x, y, radius=12, color=COLORS['navy']):
    """Draw a circular head."""
    draw.ellipse(
        [x - radius, y - radius, x + radius, y + radius],
        outline=color,
        width=STROKE_WIDTH
    )


def draw_stick_body(draw, x, y, length=40, color=COLORS['navy']):
    """Draw body line from neck to hip."""
    draw.line([(x, y), (x, y + length)], fill=color, width=STROKE_WIDTH)
    return (x, y + length)  # Return hip position


def draw_stick_arms(draw, shoulder_x, shoulder_y, left_angle, right_angle, arm_length=30, color=COLORS['navy']):
    """Draw arms at given angles (0 = horizontal, positive = up)."""
    # Left arm
    left_end_x = shoulder_x - arm_length * math.cos(math.radians(left_angle))
    left_end_y = shoulder_y - arm_length * math.sin(math.radians(left_angle))
    draw.line([(shoulder_x, shoulder_y), (left_end_x, left_end_y)], fill=color, width=STROKE_WIDTH)

    # Right arm
    right_end_x = shoulder_x + arm_length * math.cos(math.radians(right_angle))
    right_end_y = shoulder_y - arm_length * math.sin(math.radians(right_angle))
    draw.line([(shoulder_x, shoulder_y), (right_end_x, right_end_y)], fill=color, width=STROKE_WIDTH)


def draw_stick_legs(draw, hip_x, hip_y, left_angle, right_angle, leg_length=35, color=COLORS['navy']):
    """Draw legs at given angles from vertical (positive = forward)."""
    # Left leg
    left_end_x = hip_x + leg_length * math.sin(math.radians(left_angle))
    left_end_y = hip_y + leg_length * math.cos(math.radians(left_angle))
    draw.line([(hip_x, hip_y), (left_end_x, left_end_y)], fill=color, width=STROKE_WIDTH)

    # Right leg
    right_end_x = hip_x + leg_length * math.sin(math.radians(right_angle))
    right_end_y = hip_y + leg_length * math.cos(math.radians(right_angle))
    draw.line([(hip_x, hip_y), (right_end_x, right_end_y)], fill=color, width=STROKE_WIDTH)


def draw_standing_person(draw, x, y, arm_pose='down', color=COLORS['navy']):
    """Draw a standing stick figure at position (x, y is head center)."""
    # Head
    draw_stick_head(draw, x, y, radius=10, color=color)

    # Neck to shoulder
    neck_y = y + 10
    shoulder_y = neck_y + 5

    # Body
    hip_x, hip_y = draw_stick_body(draw, x, shoulder_y, length=35, color=color)

    # Arms
    if arm_pose == 'down':
        draw_stick_arms(draw, x, shoulder_y + 5, -70, -70, arm_length=25, color=color)
    elif arm_pose == 'up':
        draw_stick_arms(draw, x, shoulder_y + 5, 60, 60, arm_length=25, color=color)
    elif arm_pose == 'cheer':
        draw_stick_arms(draw, x, shoulder_y + 5, 120, 120, arm_length=25, color=color)

    # Legs
    draw_stick_legs(draw, hip_x, hip_y, -10, 10, leg_length=35, color=color)


def draw_walking_person(draw, x, y, walk_phase, color=COLORS['navy'], with_suitcase=False):
    """Draw a walking stick figure with animated legs."""
    # Walk cycle: phase 0-1 represents full cycle
    leg_swing = 25 * math.sin(walk_phase * 2 * math.pi)
    arm_swing = 15 * math.sin(walk_phase * 2 * math.pi)

    # Head
    draw_stick_head(draw, x, y, radius=10, color=color)

    # Body
    shoulder_y = y + 15
    hip_x, hip_y = draw_stick_body(draw, x, shoulder_y, length=35, color=color)

    # Arms (opposite to legs)
    draw_stick_arms(draw, x, shoulder_y + 5, -45 - arm_swing, -45 + arm_swing, arm_length=25, color=color)

    # Legs
    draw_stick_legs(draw, hip_x, hip_y, leg_swing, -leg_swing, leg_length=35, color=color)

    # Suitcase
    if with_suitcase:
        case_x = x + 20 + 5 * math.sin(walk_phase * 2 * math.pi)
        case_y = y + 45
        draw.rectangle(
            [case_x, case_y, case_x + 15, case_y + 20],
            fill=COLORS['orange'],
            outline=color,
            width=2
        )


# ============================================================================
# GIF 1: ACTIVITÉS - Horse & Rider (Hi-Res Version)
# ============================================================================

def draw_horse_hires(draw, x, y, leg_phase, tail_phase, bob_offset=0, color=COLORS['navy'], fill=COLORS['green']):
    """Draw a refined horse with proper anatomy at 800x800 scale.

    Horse anatomy:
    - Body: horizontal oval (long, not round)
    - Neck: trapezoid angling upward
    - Head: elongated oval with ear and eye
    - 4 legs with knee bends and hooves
    - Flowing tail
    """
    y = y + bob_offset
    stroke = 6  # Stroke width at hi-res (appears as 3px after resize)

    # Body - horizontal oval (horses are LONG, not round)
    body_width = 160  # Wide
    body_height = 80  # Shorter
    body_left = x - body_width // 2
    body_right = x + body_width // 2
    body_top = y - body_height // 2
    body_bottom = y + body_height // 2

    draw.ellipse(
        [body_left, body_top, body_right, body_bottom],
        fill=fill,
        outline=color,
        width=stroke
    )

    # Neck - trapezoid from front of body, angling upward
    neck_base_x = x + body_width // 2 - 30
    neck_base_y = y - body_height // 4
    neck_top_x = neck_base_x + 50
    neck_top_y = y - body_height - 40
    neck_width_base = 50
    neck_width_top = 30

    neck_points = [
        (neck_base_x - neck_width_base // 2, neck_base_y),
        (neck_top_x - neck_width_top // 2, neck_top_y),
        (neck_top_x + neck_width_top // 2, neck_top_y),
        (neck_base_x + neck_width_base // 2, neck_base_y),
    ]
    draw.polygon(neck_points, fill=fill, outline=color, width=stroke)

    # Head - elongated horizontal oval
    head_cx = neck_top_x + 45
    head_cy = neck_top_y + 15
    head_width = 70
    head_height = 40

    draw.ellipse(
        [head_cx - head_width // 2, head_cy - head_height // 2,
         head_cx + head_width // 2, head_cy + head_height // 2],
        fill=fill,
        outline=color,
        width=stroke
    )

    # Ear - triangular on back of head
    ear_base_x = head_cx - 10
    ear_base_y = head_cy - head_height // 2
    ear_points = [
        (ear_base_x - 8, ear_base_y),
        (ear_base_x, ear_base_y - 25),
        (ear_base_x + 8, ear_base_y),
    ]
    draw.polygon(ear_points, fill=fill, outline=color, width=4)

    # Eye - dot
    eye_x = head_cx + 10
    eye_y = head_cy - 5
    draw.ellipse([eye_x - 6, eye_y - 6, eye_x + 6, eye_y + 6], fill=color)

    # Muzzle line (nostril hint)
    draw.arc(
        [head_cx + head_width // 2 - 20, head_cy,
         head_cx + head_width // 2, head_cy + 15],
        start=180, end=270, fill=color, width=4
    )

    # Legs with knee bends - trot animation
    leg_hip_y = y + body_height // 2 - 10
    upper_leg_length = 50
    lower_leg_length = 50
    leg_width = 14
    hoof_width = 18
    hoof_height = 12

    # Trot phases - front pair and back pair move opposite
    front_phase = leg_phase * 2 * math.pi
    back_phase = front_phase + math.pi  # Opposite phase

    def draw_leg(hip_x, hip_y, phase, is_front=True):
        """Draw a single leg with knee bend."""
        # Swing angle for upper leg
        swing = 25 * math.sin(phase)
        # Knee bend varies with swing
        knee_bend = 25 + 15 * math.cos(phase)

        # Upper leg
        upper_angle = math.radians(-swing)
        knee_x = hip_x + upper_leg_length * math.sin(upper_angle)
        knee_y = hip_y + upper_leg_length * math.cos(upper_angle)

        # Draw upper leg as thick line
        draw.line([(hip_x, hip_y), (knee_x, knee_y)], fill=color, width=leg_width)

        # Lower leg - bends at knee
        lower_angle = upper_angle + math.radians(knee_bend)
        hoof_x = knee_x + lower_leg_length * math.sin(lower_angle)
        hoof_y = knee_y + lower_leg_length * math.cos(lower_angle)

        # Draw lower leg
        draw.line([(knee_x, knee_y), (hoof_x, hoof_y)], fill=color, width=leg_width - 2)

        # Hoof - small rounded rectangle
        draw.ellipse(
            [hoof_x - hoof_width // 2, hoof_y - 4,
             hoof_x + hoof_width // 2, hoof_y + hoof_height],
            fill=color
        )

        return (hoof_x, hoof_y)

    # Front legs (at front of body)
    front_leg_x = x + body_width // 4
    draw_leg(front_leg_x - 8, leg_hip_y, front_phase + 0.3)  # Front left
    hoof_pos = draw_leg(front_leg_x + 8, leg_hip_y, front_phase - 0.3)  # Front right

    # Back legs (at back of body)
    back_leg_x = x - body_width // 4
    draw_leg(back_leg_x - 8, leg_hip_y, back_phase + 0.3)  # Back left
    draw_leg(back_leg_x + 8, leg_hip_y, back_phase - 0.3)  # Back right

    # Tail - flowing curve from back
    tail_swing = 20 * math.sin(tail_phase * 2 * math.pi)
    tail_start_x = x - body_width // 2 + 10
    tail_start_y = y + 10

    # Draw tail as curved line segments
    tail_points = [
        (tail_start_x, tail_start_y),
        (tail_start_x - 30, tail_start_y + 30 + tail_swing * 0.5),
        (tail_start_x - 50, tail_start_y + 60 + tail_swing),
        (tail_start_x - 40, tail_start_y + 90 + tail_swing * 0.7),
    ]
    draw.line(tail_points, fill=color, width=10)

    # Tail hair strands
    for i, offset in enumerate([-15, 0, 15]):
        strand_swing = tail_swing + offset * 0.3
        end_x = tail_start_x - 55 + offset
        end_y = tail_start_y + 100 + strand_swing
        draw.line(
            [(tail_start_x - 40, tail_start_y + 90 + tail_swing * 0.7), (end_x, end_y)],
            fill=color, width=6
        )

    return hoof_pos  # Return position for dust


def draw_rider_hires(draw, x, y, bob_offset=0, arm_sway=0, color=COLORS['navy'], head_pos=None):
    """Draw a refined stick figure rider at 800x800 scale."""
    y = y + bob_offset
    rider_y = y - 100  # Above horse body
    stroke = 6  # Stroke width at hi-res

    # Head - proportional circle
    head_radius = 24
    head_x = x + 20
    head_y = rider_y - 40
    draw.ellipse(
        [head_x - head_radius, head_y - head_radius,
         head_x + head_radius, head_y + head_radius],
        outline=color, width=stroke
    )

    # Torso - rectangular, sitting upright
    shoulder_y = head_y + head_radius + 5
    hip_y = shoulder_y + 50
    draw.line([(head_x, shoulder_y), (head_x - 5, hip_y)], fill=color, width=stroke)

    # Left arm (holding reins) - extends forward to horse head
    rein_end_x = head_pos[0] if head_pos else x + 100
    rein_end_y = head_pos[1] if head_pos else rider_y
    draw.line([(head_x - 5, shoulder_y + 10), (head_x + 30, shoulder_y + 30)], fill=color, width=stroke)
    # Reins line
    draw.line([(head_x + 30, shoulder_y + 30), (rein_end_x, rein_end_y)], fill=color, width=3)

    # Right arm (raised, waving)
    arm_angle = 50 + arm_sway * 15
    arm_length = 50
    arm_end_x = head_x + arm_length * math.cos(math.radians(arm_angle))
    arm_end_y = shoulder_y + 10 - arm_length * math.sin(math.radians(arm_angle))
    draw.line([(head_x + 5, shoulder_y + 10), (arm_end_x, arm_end_y)], fill=color, width=stroke)

    # Legs (bent, hanging on each side of horse)
    # Left leg (visible, hanging down-left)
    knee_l_x = head_x - 30
    knee_l_y = hip_y + 30
    foot_l_x = knee_l_x - 10
    foot_l_y = knee_l_y + 35
    draw.line([(head_x - 5, hip_y), (knee_l_x, knee_l_y)], fill=color, width=stroke)
    draw.line([(knee_l_x, knee_l_y), (foot_l_x, foot_l_y)], fill=color, width=stroke)

    # Right leg (hanging down-right)
    knee_r_x = head_x + 30
    knee_r_y = hip_y + 25
    foot_r_x = knee_r_x + 10
    foot_r_y = knee_r_y + 35
    draw.line([(head_x - 5, hip_y), (knee_r_x, knee_r_y)], fill=color, width=stroke)
    draw.line([(knee_r_x, knee_r_y), (foot_r_x, foot_r_y)], fill=color, width=stroke)


def draw_ground_path_hires(draw, offset, y_pos, color=COLORS['gray']):
    """Draw scrolling ground path at 800x800 scale."""
    width = HIRES_SIZE

    # Main ground line - dashed
    dash_length = 40
    gap_length = 20
    for i in range(-2, 20):
        dash_x = (i * (dash_length + gap_length) - offset) % (width + 200) - 100
        if -dash_length <= dash_x <= width:
            draw.line(
                [(max(0, dash_x), y_pos), (min(width, dash_x + dash_length), y_pos)],
                fill=color, width=4
            )


def draw_dust_puffs_hires(draw, x, y, frame, particles, scale=2):
    """Draw animated dust particles at hi-res scale."""
    for p in particles:
        age = frame - p['start_frame']
        if 0 <= age < p['lifetime']:
            alpha = int(200 * (1 - age / p['lifetime']))
            size = (p['size'] + age * 1) * scale
            px = p['x'] * scale + p['vx'] * age * scale
            py = p['y'] * scale + p['vy'] * age * scale

            dust_color = COLORS['gray'][:3] + (alpha,)
            draw.ellipse(
                [px - size, py - size, px + size, py + size],
                fill=dust_color
            )


def generate_activites_gif():
    """Generate the activites.gif - horse and rider animation at hi-res."""
    print("Generating activites.gif (hi-res)...")
    frames = []

    # Positions at hi-res scale (800x800)
    horse_x = HIRES_SIZE // 2 - 40
    horse_y = HIRES_SIZE // 2 + 40
    ground_y = HIRES_SIZE - 160

    # Pre-generate dust particles (at original scale, will be scaled during draw)
    dust_particles = []
    for i in range(TOTAL_FRAMES):
        if i % 3 == 0:  # New dust every 3 frames
            dust_particles.append({
                'x': (horse_x // 2) - 60 + random.randint(-10, 10),
                'y': (ground_y // 2) - 10,
                'vx': random.uniform(-3, -1),
                'vy': random.uniform(-1.5, -0.5),
                'size': random.randint(6, 12),
                'start_frame': i,
                'lifetime': 10
            })

    for i in range(TOTAL_FRAMES):
        # Create hi-res frame
        frame = create_hires_frame()
        draw = ImageDraw.Draw(frame)

        # Calculate animation phases
        leg_phase = i / 6  # Complete leg cycle every 6 frames
        tail_phase = i / 8
        bob_offset = 8 * math.sin(leg_phase * 2 * math.pi)  # 8px at hi-res = 4px at output
        arm_sway = math.sin(i / 4 * math.pi)
        ground_offset = i * 16  # Ground scrolls (doubled for hi-res)

        # Draw ground path
        draw_ground_path_hires(draw, ground_offset, ground_y)

        # Draw dust
        draw_dust_puffs_hires(draw, horse_x, ground_y, i, dust_particles)

        # Calculate head position for reins
        head_x = horse_x + 80 + 45  # neck_top_x + head offset
        head_y = horse_y - 80 - 40 + 15 + int(bob_offset)  # neck_top_y + head_cy offset

        # Draw horse
        draw_horse_hires(draw, horse_x, horse_y, leg_phase, tail_phase, int(bob_offset))

        # Draw rider
        draw_rider_hires(draw, horse_x, horse_y, int(bob_offset), arm_sway, head_pos=(head_x, head_y))

        # Resize to output size with LANCZOS
        frames.append(resize_frame(frame))

    return save_gif(frames, 'activites.gif')


# ============================================================================
# GIF 2: NAUTIQUE - Jet Ski with Rider (Hi-Res Version)
# ============================================================================

def draw_jetski_hires(draw, x, y, tilt=0, color=COLORS['navy'], fill=COLORS['green']):
    """Draw a refined jet ski hull at 800x800 scale.

    Hull: smooth elongated wedge shape taking ~40% canvas width
    - Pointed/narrow at front with upward curve (boat bow)
    - Wider at back
    - Seat bump in middle
    - Handlebars in front
    """
    stroke = 6

    # Hull dimensions - takes 40% of 800 = 320px
    hull_length = 320
    hull_height = 60

    # Hull - smooth wedge shape with bow curve
    # Front tip is elevated and narrow, back is wider and flat
    hull_points = [
        # Front bow (elevated, narrow)
        (x + hull_length // 2 + 20, y - 30),  # Tip
        (x + hull_length // 2, y - 35),  # Bow curve top
        (x + hull_length // 4, y - 45),  # Upper front curve

        # Top deck
        (x, y - 50),  # Mid-top
        (x - hull_length // 4, y - 45),  # Upper rear

        # Rear
        (x - hull_length // 2 + 20, y - 35),  # Rear top corner
        (x - hull_length // 2, y + 5),  # Rear bottom

        # Bottom hull
        (x - hull_length // 4, y + 20),  # Rear underside
        (x, y + 25),  # Mid bottom
        (x + hull_length // 4, y + 15),  # Front underside
        (x + hull_length // 2, y - 5),  # Front bottom curve
    ]
    draw.polygon(hull_points, fill=fill, outline=color, width=stroke)

    # Seat bump - raised section in middle-back
    seat_points = [
        (x - 60, y - 45),
        (x - 50, y - 65),
        (x + 20, y - 65),
        (x + 40, y - 50),
    ]
    draw.polygon(seat_points, fill=fill, outline=color, width=stroke)

    # Handlebars - two angled lines
    handlebar_base_x = x + 80
    handlebar_base_y = y - 55
    handlebar_top_y = y - 100

    # Stem
    draw.line(
        [(handlebar_base_x, handlebar_base_y), (handlebar_base_x + 10, handlebar_top_y)],
        fill=color, width=8
    )
    # Crossbar
    draw.line(
        [(handlebar_base_x - 15, handlebar_top_y - 5), (handlebar_base_x + 35, handlebar_top_y + 5)],
        fill=color, width=8
    )
    # Grips
    draw.ellipse(
        [handlebar_base_x - 20, handlebar_top_y - 12, handlebar_base_x - 8, handlebar_top_y + 2],
        fill=color
    )
    draw.ellipse(
        [handlebar_base_x + 28, handlebar_top_y - 2, handlebar_base_x + 40, handlebar_top_y + 12],
        fill=color
    )

    return (handlebar_base_x - 15, handlebar_top_y - 5, handlebar_base_x + 35, handlebar_top_y + 5)


def draw_jetski_rider_hires(draw, x, y, tilt=0, scarf_phase=0, handlebar_pos=None, color=COLORS['navy']):
    """Draw refined jet ski rider at 800x800 scale.

    - Leaning forward dynamically
    - Arms gripping handlebars
    - Scarf/bandana trailing
    - Smile on face
    """
    stroke = 6

    # Rider position (sitting on seat)
    rider_x = x - 20
    rider_y = y - 130

    # Head - circle with forward lean
    head_radius = 26
    head_x = rider_x + 30
    head_y = rider_y - 20
    draw.ellipse(
        [head_x - head_radius, head_y - head_radius,
         head_x + head_radius, head_y + head_radius],
        outline=color, width=stroke
    )

    # Smile - arc on face
    smile_y = head_y + 5
    draw.arc(
        [head_x - 12, smile_y - 8, head_x + 12, smile_y + 8],
        start=20, end=160, fill=color, width=4
    )

    # Eyes - two dots
    draw.ellipse([head_x - 10, head_y - 8, head_x - 4, head_y - 2], fill=color)
    draw.ellipse([head_x + 4, head_y - 8, head_x + 10, head_y - 2], fill=color)

    # Scarf/bandana trailing - 3 wavy lines
    scarf_base_x = head_x - head_radius + 5
    scarf_base_y = head_y

    for i, offset in enumerate([0, 8, 16]):
        wave = 10 * math.sin(scarf_phase + i * 0.5)
        scarf_points = [
            (scarf_base_x, scarf_base_y + offset),
            (scarf_base_x - 30, scarf_base_y + offset + wave * 0.5 + tilt),
            (scarf_base_x - 60, scarf_base_y + offset + wave + tilt * 1.5),
            (scarf_base_x - 80, scarf_base_y + offset + wave * 0.7 + tilt * 2),
        ]
        draw.line(scarf_points, fill=COLORS['orange'], width=6 - i)

    # Torso - bent forward (dynamic riding posture)
    shoulder_x = head_x - 5
    shoulder_y = head_y + head_radius + 10
    hip_x = rider_x - 10
    hip_y = y - 70

    draw.line([(shoulder_x, shoulder_y), (hip_x, hip_y)], fill=color, width=stroke)

    # Arms - extended forward to handlebars
    if handlebar_pos:
        left_grip = (handlebar_pos[0], handlebar_pos[1])
        right_grip = (handlebar_pos[2], handlebar_pos[3])
    else:
        left_grip = (x + 65, y - 105)
        right_grip = (x + 115, y - 95)

    # Left arm
    elbow_l_x = shoulder_x + 20
    elbow_l_y = shoulder_y + 15
    draw.line([(shoulder_x, shoulder_y), (elbow_l_x, elbow_l_y)], fill=color, width=stroke)
    draw.line([(elbow_l_x, elbow_l_y), left_grip], fill=color, width=stroke)

    # Right arm
    elbow_r_x = shoulder_x + 35
    elbow_r_y = shoulder_y + 10
    draw.line([(shoulder_x + 5, shoulder_y), (elbow_r_x, elbow_r_y)], fill=color, width=stroke)
    draw.line([(elbow_r_x, elbow_r_y), right_grip], fill=color, width=stroke)

    # Legs - bent, sitting on seat
    knee_y = hip_y + 40

    # Left leg
    knee_l_x = hip_x - 25
    foot_l_x = knee_l_x + 10
    foot_l_y = knee_y + 35
    draw.line([(hip_x, hip_y), (knee_l_x, knee_y)], fill=color, width=stroke)
    draw.line([(knee_l_x, knee_y), (foot_l_x, foot_l_y)], fill=color, width=stroke)

    # Right leg
    knee_r_x = hip_x + 35
    foot_r_x = knee_r_x + 15
    foot_r_y = knee_y + 30
    draw.line([(hip_x, hip_y), (knee_r_x, knee_y)], fill=color, width=stroke)
    draw.line([(knee_r_x, knee_y), (foot_r_x, foot_r_y)], fill=color, width=stroke)


def draw_waves_hires(draw, offset, y_base, color=COLORS['navy']):
    """Draw scrolling wave lines at 800x800 scale."""
    wave_amplitude = 16  # Doubled for hi-res
    wave_length = 120
    width = HIRES_SIZE

    for wave_idx, wave_y in enumerate([y_base, y_base + 50, y_base + 100]):
        points = []
        phase_offset = wave_idx * 0.4
        for px in range(-40, width + 80, 8):
            adjusted_x = px + offset
            py = wave_y + wave_amplitude * math.sin(2 * math.pi * adjusted_x / wave_length + phase_offset)
            points.append((int(px), int(py)))

        if len(points) > 1:
            draw.line(points, fill=color, width=6)


def draw_rooster_tail_hires(draw, x, y, phase, color=COLORS['white']):
    """Draw V-shaped rooster-tail water spray behind jet ski."""
    # Pulsing size
    pulse = 0.7 + 0.3 * math.sin(phase * 4)
    base_height = 80 * pulse
    spread = 60 * pulse

    spray_base_x = x - 140
    spray_base_y = y + 20

    # V-shape spray
    # Left spray arm
    for i in range(5):
        progress = i / 4
        spray_x = spray_base_x - spread * progress - 10 * progress
        spray_y = spray_base_y - base_height * progress
        size = (12 - i * 2) * pulse
        alpha = int(200 * (1 - progress * 0.5))
        spray_color = color[:3] + (alpha,)
        draw.ellipse(
            [spray_x - size, spray_y - size, spray_x + size, spray_y + size],
            fill=spray_color
        )

    # Right spray arm
    for i in range(5):
        progress = i / 4
        spray_x = spray_base_x - spread * progress - 10 * progress
        spray_y = spray_base_y + base_height * progress * 0.3  # Less downward
        size = (10 - i * 2) * pulse
        alpha = int(180 * (1 - progress * 0.5))
        spray_color = color[:3] + (alpha,)
        draw.ellipse(
            [spray_x - size, spray_y - size, spray_x + size, spray_y + size],
            fill=spray_color
        )

    # Center spray particles
    for i in range(8):
        angle = math.radians(-120 + i * 10)
        dist = (30 + i * 8) * pulse
        px = spray_base_x + dist * math.cos(angle)
        py = spray_base_y + dist * math.sin(angle)
        size = (8 - i * 0.5) * pulse
        alpha = int(220 * (1 - i / 10))
        spray_color = color[:3] + (alpha,)
        draw.ellipse(
            [px - size, py - size, px + size, py + size],
            fill=spray_color
        )


def draw_splash_particles_hires(draw, x, y, frame, particles):
    """Draw splash particles at hi-res scale."""
    for p in particles:
        age = frame - p['start_frame']
        if 0 <= age < p['lifetime']:
            progress = age / p['lifetime']
            alpha = int(220 * (1 - progress))

            # Parabolic motion (scaled for hi-res)
            px = p['x'] * 2 + p['vx'] * age * 2
            py = p['y'] * 2 + p['vy'] * age * 2 + 0.8 * age * age

            size = p['size'] * 2 * (1 - progress * 0.4)

            splash_color = COLORS['white'][:3] + (alpha,)
            draw.ellipse(
                [px - size, py - size, px + size, py + size],
                fill=splash_color
            )


def generate_nautique_gif():
    """Generate the nautique.gif - jet ski with rider at hi-res."""
    print("Generating nautique.gif (hi-res)...")
    frames = []

    # Positions at hi-res scale (800x800)
    jetski_x = HIRES_SIZE // 2
    jetski_base_y = HIRES_SIZE // 2 - 60
    wave_y = HIRES_SIZE // 2 + 120

    # Pre-generate splash particles (at original scale, will be scaled during draw)
    splash_particles = []
    for i in range(TOTAL_FRAMES):
        if i % 4 == 0:  # Regular splash
            for _ in range(4):
                splash_particles.append({
                    'x': jetski_x // 2 - 70 + random.randint(-10, 10),
                    'y': jetski_base_y // 2 + 15,
                    'vx': random.uniform(-4, -1),
                    'vy': random.uniform(-5, -2),
                    'size': random.randint(4, 8),
                    'start_frame': i,
                    'lifetime': 12
                })
        if i % 8 == 0:  # Big splash
            for _ in range(6):
                splash_particles.append({
                    'x': jetski_x // 2 - 70 + random.randint(-15, 15),
                    'y': jetski_base_y // 2 + 15,
                    'vx': random.uniform(-6, -2),
                    'vy': random.uniform(-7, -3),
                    'size': random.randint(6, 12),
                    'start_frame': i,
                    'lifetime': 14
                })

    for i in range(TOTAL_FRAMES):
        # Create hi-res frame
        frame = create_hires_frame()
        draw = ImageDraw.Draw(frame)

        # Wave motion
        wave_phase = i / TOTAL_FRAMES * 2 * math.pi
        bounce = 16 * math.sin(wave_phase)  # Doubled for hi-res
        tilt = 5 * math.sin(wave_phase)
        wave_offset = i * 20  # Doubled for hi-res
        scarf_phase = i / 3 * math.pi

        # Draw waves (behind everything)
        draw_waves_hires(draw, wave_offset, wave_y)

        # Draw splash particles
        draw_splash_particles_hires(draw, jetski_x, jetski_base_y, i, splash_particles)

        # Draw rooster-tail spray
        draw_rooster_tail_hires(draw, jetski_x, int(jetski_base_y + bounce), wave_phase)

        # Draw jet ski
        handlebar_pos = draw_jetski_hires(draw, jetski_x, int(jetski_base_y + bounce), tilt)

        # Draw rider
        draw_jetski_rider_hires(
            draw, jetski_x, int(jetski_base_y + bounce),
            tilt, scarf_phase, handlebar_pos
        )

        # Resize to output size with LANCZOS
        frames.append(resize_frame(frame))

    return save_gif(frames, 'nautique.gif')


# ============================================================================
# GIF 3: HÉBERGEMENTS - Welcome Scene
# ============================================================================

def draw_house(draw, x, y, door_open=0, glow_alpha=0, color=COLORS['navy'], fill=COLORS['green']):
    """Draw house with optional open door and glow."""
    # House body
    house_left = x - 70
    house_right = x + 70
    house_top = y - 40
    house_bottom = y + 80

    draw.rectangle(
        [house_left, house_top, house_right, house_bottom],
        fill=COLORS['white'],
        outline=color,
        width=STROKE_WIDTH
    )

    # Roof
    roof_peak = y - 100
    roof_points = [
        (house_left - 10, house_top),
        (x, roof_peak),
        (house_right + 10, house_top),
    ]
    draw.polygon(roof_points, fill=fill, outline=color, width=STROKE_WIDTH)

    # Windows
    window_size = 25
    # Left window
    draw.rectangle(
        [house_left + 15, house_top + 20, house_left + 15 + window_size, house_top + 20 + window_size],
        fill=COLORS['orange'] if glow_alpha > 0 else COLORS['gray'],
        outline=color,
        width=2
    )
    # Right window
    draw.rectangle(
        [house_right - 15 - window_size, house_top + 20, house_right - 15, house_top + 20 + window_size],
        fill=COLORS['orange'] if glow_alpha > 0 else COLORS['gray'],
        outline=color,
        width=2
    )

    # Door
    door_width = 30
    door_height = 50
    door_x = x - door_width // 2
    door_y = house_bottom - door_height

    # Door glow (when open)
    if glow_alpha > 0:
        glow_color = COLORS['orange'][:3] + (int(glow_alpha * 150),)
        draw.rectangle(
            [door_x, door_y, door_x + door_width, house_bottom],
            fill=glow_color
        )

    # Door frame
    if door_open < 0.5:
        # Closed or slightly open
        draw.rectangle(
            [door_x, door_y, door_x + door_width, house_bottom],
            fill=COLORS['green'] if door_open == 0 else COLORS['orange'],
            outline=color,
            width=2
        )
    else:
        # Open door (perspective)
        open_width = door_width * (1 - door_open * 0.7)
        draw.polygon(
            [
                (door_x, door_y),
                (door_x + open_width, door_y + 5),
                (door_x + open_width, house_bottom - 5),
                (door_x, house_bottom),
            ],
            fill=fill,
            outline=color,
            width=2
        )

    # Welcome mat
    mat_y = house_bottom + 2
    draw.rectangle(
        [x - 25, mat_y, x + 25, mat_y + 10],
        fill=COLORS['orange'],
        outline=color,
        width=1
    )


def draw_palm_tree(draw, x, y, sway=0, color=COLORS['navy'], leaf_color=COLORS['green']):
    """Draw palm tree with swaying leaves."""
    # Trunk
    trunk_points = [
        (x - 8, y),
        (x - 5, y - 80),
        (x + 5, y - 80),
        (x + 8, y),
    ]
    draw.polygon(trunk_points, fill=COLORS['orange'], outline=color, width=2)

    # Leaves
    leaf_length = 50
    base_angles = [-70, -40, -10, 20, 50]

    for angle in base_angles:
        actual_angle = math.radians(angle + sway - 90)
        end_x = x + leaf_length * math.cos(actual_angle)
        end_y = (y - 80) + leaf_length * math.sin(actual_angle)

        # Draw curved leaf (simplified as line for now)
        draw.line([(x, y - 80), (int(end_x), int(end_y))], fill=leaf_color, width=5)


def draw_heart(draw, x, y, size, alpha):
    """Draw a heart shape."""
    if alpha <= 0:
        return

    color = COLORS['orange'][:3] + (int(alpha * 255),)

    # Simple heart using circles and triangle
    r = size // 3
    draw.ellipse([x - r * 2, y - r, x, y + r], fill=color)
    draw.ellipse([x, y - r, x + r * 2, y + r], fill=color)
    draw.polygon(
        [(x - r * 2, y), (x + r * 2, y), (x, y + r * 2)],
        fill=color
    )


def generate_hebergements_gif():
    """Generate the hebergements.gif - welcome scene."""
    print("Generating hebergements.gif...")
    frames = []

    house_x = WIDTH // 2 - 40
    house_y = HEIGHT // 2 + 20
    palm_x = WIDTH - 80
    palm_y = HEIGHT // 2 + 100

    person_start_x = WIDTH - 50
    person_end_x = house_x + 10

    for i in range(TOTAL_FRAMES):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Animation phases
        walk_progress = min(i / 14, 1.0)  # Walk takes 14 frames
        door_progress = max(0, (i - 12) / 6) if i > 12 else 0  # Door opens after frame 12
        glow_progress = max(0, (i - 14) / 4) if i > 14 else 0
        heart_progress = max(0, (i - 16) / 4) if i > 16 else 0
        palm_sway = 5 * math.sin(i / 6 * math.pi)

        # Draw house
        draw_house(draw, house_x, house_y, min(door_progress, 1.0), min(glow_progress, 1.0))

        # Draw palm tree
        draw_palm_tree(draw, palm_x, palm_y, palm_sway)

        # Draw walking person
        if walk_progress < 1.0:
            person_x = person_start_x - (person_start_x - person_end_x) * walk_progress
            walk_phase = i / 4
            draw_walking_person(draw, int(person_x), HEIGHT // 2 + 30, walk_phase, with_suitcase=True)
        else:
            # Standing at door
            draw_walking_person(draw, person_end_x, HEIGHT // 2 + 30, 0, with_suitcase=True)

        # Draw heart
        if heart_progress > 0:
            heart_y = HEIGHT // 2 - 10 - heart_progress * 20
            draw_heart(draw, person_end_x, int(heart_y), 20, min(heart_progress, 1.0))

        frames.append(frame)

    return save_gif(frames, 'hebergements.gif')


# ============================================================================
# GIF 4: ÉVÉNEMENTS - Team Building Celebration
# ============================================================================

def draw_campfire(draw, x, y, flame_frame, color=COLORS['navy']):
    """Draw campfire with flickering flame."""
    # Logs
    draw.line([(x - 25, y + 10), (x + 25, y + 10)], fill=color, width=6)
    draw.line([(x - 20, y + 5), (x + 20, y + 15)], fill=color, width=5)

    # Flame shapes (alternate between frames)
    flame_colors = [COLORS['orange'], (255, 200, 50)]  # Orange and yellow
    flame_color = flame_colors[flame_frame % 2]

    if flame_frame % 3 == 0:
        flame_points = [
            (x, y - 40),
            (x - 20, y),
            (x - 5, y - 15),
            (x + 5, y - 10),
            (x + 20, y),
        ]
    elif flame_frame % 3 == 1:
        flame_points = [
            (x - 5, y - 45),
            (x - 25, y - 5),
            (x - 10, y - 20),
            (x + 10, y - 15),
            (x + 25, y - 5),
        ]
    else:
        flame_points = [
            (x + 5, y - 42),
            (x - 22, y - 3),
            (x - 8, y - 18),
            (x + 8, y - 12),
            (x + 22, y - 3),
        ]

    draw.polygon(flame_points, fill=flame_color, outline=COLORS['orange'], width=2)


def draw_banner(draw, x, y, wave_offset, color=COLORS['navy'], fill=COLORS['orange']):
    """Draw waving banner."""
    # Pole
    draw.line([(x, y), (x, y - 60)], fill=color, width=4)

    # Banner
    wave = 5 * math.sin(wave_offset)
    banner_points = [
        (x, y - 55),
        (x + 60, y - 50 + wave),
        (x + 50, y - 35 + wave * 0.5),
        (x + 60, y - 20 + wave),
        (x, y - 25),
    ]
    draw.polygon(banner_points, fill=fill, outline=color, width=2)


def draw_confetti(draw, particles, frame):
    """Draw confetti particles."""
    confetti_colors = [COLORS['orange'], COLORS['green'], COLORS['white'], (255, 200, 50)]

    for p in particles:
        age = frame - p['start_frame']
        if 0 <= age < p['lifetime']:
            progress = age / p['lifetime']
            alpha = int(255 * (1 - progress * 0.7))

            px = p['x'] + p['vx'] * age
            py = p['y'] + p['vy'] * age + 0.3 * age * age  # Gravity

            color = confetti_colors[p['color_idx'] % len(confetti_colors)]
            conf_color = color[:3] + (alpha,) if len(color) == 3 else color[:3] + (alpha,)

            size = p['size']
            draw.rectangle(
                [px - size, py - size, px + size, py + size],
                fill=conf_color
            )


def generate_evenements_gif():
    """Generate the evenements.gif - team celebration."""
    print("Generating evenements.gif...")
    frames = []

    center_x = WIDTH // 2
    fire_y = HEIGHT // 2 + 80

    # Person positions (semicircle)
    person_positions = [
        (center_x - 80, HEIGHT // 2 + 20),
        (center_x - 30, HEIGHT // 2),
        (center_x + 30, HEIGHT // 2),
        (center_x + 80, HEIGHT // 2 + 20),
    ]

    # Pre-generate confetti
    confetti_particles = []
    for i in range(TOTAL_FRAMES):
        if 6 <= i <= 10:  # Confetti burst during cheer
            for _ in range(3):
                confetti_particles.append({
                    'x': center_x + random.randint(-60, 60),
                    'y': HEIGHT // 2 - 60,
                    'vx': random.uniform(-2, 2),
                    'vy': random.uniform(-3, 0),
                    'size': random.randint(3, 6),
                    'start_frame': i,
                    'lifetime': 15,
                    'color_idx': random.randint(0, 3)
                })

    for i in range(TOTAL_FRAMES):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Animation phases
        # Cheer cycle: arms down (0-5), arms up (6-12), arms down (13-18), hold (19-23)
        if i < 6:
            arm_phase = 'down'
            jump_offset = 0
        elif i < 13:
            arm_phase = 'up'
            # Center person jumps
            jump_progress = (i - 6) / 6
            jump_offset = -15 * math.sin(jump_progress * math.pi)
        elif i < 19:
            arm_phase = 'down'
            jump_offset = 0
        else:
            arm_phase = 'down'
            jump_offset = 0

        flame_frame = i
        banner_wave = i / 4 * math.pi

        # Draw banner
        draw_banner(draw, center_x, HEIGHT // 2 - 60, banner_wave)

        # Draw confetti
        draw_confetti(draw, confetti_particles, i)

        # Draw campfire
        draw_campfire(draw, center_x, fire_y, flame_frame)

        # Draw people
        for idx, (px, py) in enumerate(person_positions):
            is_center = idx in [1, 2]
            y_offset = int(jump_offset) if is_center and arm_phase == 'up' else 0

            # Determine arm pose
            if arm_phase == 'up':
                pose = 'cheer'
            else:
                pose = 'down'

            draw_standing_person(draw, px, py + y_offset, arm_pose=pose)

        frames.append(frame)

    return save_gif(frames, 'evenements.gif')


# ============================================================================
# Main
# ============================================================================

def main():
    """Generate GIF files."""
    import sys

    print("=" * 60)
    print("Generating Service Type GIFs v2 (Hi-Res Quality)")
    print("=" * 60)
    print(f"Output directory: {OUTPUT_DIR}")
    print(f"Frame duration: {FRAME_DURATION}ms")
    print(f"Output size: {OUTPUT_SIZE}x{OUTPUT_SIZE}px")
    print(f"Working size: {HIRES_SIZE}x{HIRES_SIZE}px (for activites & nautique)")
    print(f"Total frames: {TOTAL_FRAMES}")
    print()

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    # Check for command line args to generate specific GIFs
    args = sys.argv[1:] if len(sys.argv) > 1 else ['all']

    if 'all' in args:
        generate_activites_gif()
        generate_nautique_gif()
        generate_hebergements_gif()
        generate_evenements_gif()
    else:
        if 'activites' in args or 'horse' in args:
            generate_activites_gif()
        if 'nautique' in args or 'jetski' in args:
            generate_nautique_gif()
        if 'hebergements' in args or 'house' in args:
            generate_hebergements_gif()
        if 'evenements' in args or 'events' in args:
            generate_evenements_gif()

    print()
    print("=" * 60)
    print("GIF generation complete!")
    print("=" * 60)


if __name__ == '__main__':
    main()
