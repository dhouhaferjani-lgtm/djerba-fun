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

WIDTH = 400
HEIGHT = 400
FRAME_DURATION = 70  # milliseconds
TOTAL_FRAMES = 24
STROKE_WIDTH = 3

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
# GIF 1: ACTIVITÉS - Horse & Rider
# ============================================================================

def draw_horse(draw, x, y, leg_phase, tail_phase, bob_offset=0, color=COLORS['navy'], fill=COLORS['green']):
    """Draw a horse with animated legs and tail."""
    y = y + bob_offset

    # Body (ellipse)
    body_width = 80
    body_height = 45
    draw.ellipse(
        [x - body_width//2, y - body_height//2, x + body_width//2, y + body_height//2],
        fill=fill,
        outline=color,
        width=STROKE_WIDTH
    )

    # Neck
    neck_start_x = x + body_width//2 - 10
    neck_start_y = y - body_height//4
    neck_end_x = neck_start_x + 25
    neck_end_y = y - body_height - 10
    draw.line([(neck_start_x, neck_start_y), (neck_end_x, neck_end_y)], fill=color, width=8)

    # Head
    head_x = neck_end_x + 15
    head_y = neck_end_y + 5
    head_points = [
        (neck_end_x, neck_end_y - 5),
        (head_x, head_y - 15),
        (head_x + 20, head_y - 10),
        (head_x + 25, head_y),
        (head_x + 20, head_y + 5),
        (neck_end_x + 5, neck_end_y + 10),
    ]
    draw.polygon(head_points, fill=fill, outline=color, width=STROKE_WIDTH)

    # Ear
    ear_x = head_x + 5
    ear_y = head_y - 20
    draw.polygon(
        [(ear_x, ear_y + 15), (ear_x + 5, ear_y), (ear_x + 10, ear_y + 15)],
        fill=fill,
        outline=color,
        width=2
    )

    # Eye
    draw.ellipse([head_x + 8, head_y - 8, head_x + 14, head_y - 2], fill=color)

    # Legs - trot animation
    leg_length = 50
    leg_y_start = y + body_height//2 - 5

    # Front legs
    front_leg_x = x + body_width//4
    fl_angle1 = 20 * math.sin(leg_phase * 2 * math.pi)
    fl_angle2 = 20 * math.sin(leg_phase * 2 * math.pi + math.pi)

    # Front left
    fl1_end_x = front_leg_x + leg_length * math.sin(math.radians(fl_angle1))
    fl1_end_y = leg_y_start + leg_length * math.cos(math.radians(fl_angle1))
    draw.line([(front_leg_x, leg_y_start), (fl1_end_x, fl1_end_y)], fill=color, width=6)

    # Front right (slightly offset)
    fl2_end_x = front_leg_x + 5 + leg_length * math.sin(math.radians(fl_angle2))
    fl2_end_y = leg_y_start + leg_length * math.cos(math.radians(fl_angle2))
    draw.line([(front_leg_x + 5, leg_y_start), (fl2_end_x, fl2_end_y)], fill=color, width=6)

    # Back legs
    back_leg_x = x - body_width//4
    bl_angle1 = 20 * math.sin(leg_phase * 2 * math.pi + math.pi)
    bl_angle2 = 20 * math.sin(leg_phase * 2 * math.pi)

    # Back left
    bl1_end_x = back_leg_x + leg_length * math.sin(math.radians(bl_angle1))
    bl1_end_y = leg_y_start + leg_length * math.cos(math.radians(bl_angle1))
    draw.line([(back_leg_x, leg_y_start), (bl1_end_x, bl1_end_y)], fill=color, width=6)

    # Back right
    bl2_end_x = back_leg_x + 5 + leg_length * math.sin(math.radians(bl_angle2))
    bl2_end_y = leg_y_start + leg_length * math.cos(math.radians(bl_angle2))
    draw.line([(back_leg_x + 5, leg_y_start), (bl2_end_x, bl2_end_y)], fill=color, width=6)

    # Tail
    tail_swing = 15 * math.sin(tail_phase * 2 * math.pi)
    tail_start_x = x - body_width//2
    tail_start_y = y
    tail_points = [
        (tail_start_x, tail_start_y),
        (tail_start_x - 20, tail_start_y + 20 + tail_swing),
        (tail_start_x - 30, tail_start_y + 40 + tail_swing),
    ]
    draw.line(tail_points, fill=color, width=5)

    return (bl1_end_x, fl1_end_y)  # Return hoof position for dust


def draw_rider_on_horse(draw, x, y, bob_offset=0, arm_sway=0, color=COLORS['navy']):
    """Draw a stick figure rider sitting on horse."""
    y = y + bob_offset
    rider_y = y - 60  # Above horse body

    # Head
    draw_stick_head(draw, x + 10, rider_y - 25, radius=12, color=color)

    # Body (leaning slightly forward)
    body_top = rider_y - 13
    body_bottom = rider_y + 20
    draw.line([(x + 10, body_top), (x + 5, body_bottom)], fill=color, width=STROKE_WIDTH)

    # Raised arm (right, waving)
    arm_angle = 60 + arm_sway * 10
    arm_end_x = x + 10 + 30 * math.cos(math.radians(arm_angle))
    arm_end_y = body_top + 5 - 30 * math.sin(math.radians(arm_angle))
    draw.line([(x + 10, body_top + 5), (arm_end_x, arm_end_y)], fill=color, width=STROKE_WIDTH)

    # Left arm (holding reins)
    draw.line([(x + 10, body_top + 5), (x + 35, body_top + 25)], fill=color, width=STROKE_WIDTH)

    # Legs (bent, sitting position)
    draw.line([(x + 5, body_bottom), (x + 25, body_bottom + 15)], fill=color, width=STROKE_WIDTH)
    draw.line([(x + 5, body_bottom), (x - 15, body_bottom + 15)], fill=color, width=STROKE_WIDTH)


def draw_ground_path(draw, offset, y_pos, color=COLORS['gray']):
    """Draw scrolling ground path."""
    # Main ground line
    draw.line([(0, y_pos), (WIDTH, y_pos)], fill=color, width=2)

    # Dotted path marks
    for i in range(-1, 15):
        mark_x = (i * 40 - offset) % (WIDTH + 80) - 40
        if 0 <= mark_x <= WIDTH:
            draw.line([(mark_x, y_pos + 5), (mark_x + 20, y_pos + 5)], fill=color, width=2)


def draw_dust_puffs(draw, x, y, frame, particles):
    """Draw animated dust particles."""
    for p in particles:
        age = frame - p['start_frame']
        if 0 <= age < p['lifetime']:
            alpha = int(255 * (1 - age / p['lifetime']))
            size = p['size'] + age * 0.5
            px = p['x'] + p['vx'] * age
            py = p['y'] + p['vy'] * age

            dust_color = COLORS['gray'][:3] + (alpha,)
            draw.ellipse(
                [px - size, py - size, px + size, py + size],
                fill=dust_color
            )


def generate_activites_gif():
    """Generate the activites.gif - horse and rider animation."""
    print("Generating activites.gif...")
    frames = []

    horse_x = WIDTH // 2 - 20
    horse_y = HEIGHT // 2 + 20
    ground_y = HEIGHT - 80

    # Pre-generate dust particles
    dust_particles = []
    for i in range(TOTAL_FRAMES):
        if i % 3 == 0:  # New dust every 3 frames
            dust_particles.append({
                'x': horse_x - 60 + random.randint(-10, 10),
                'y': ground_y - 10,
                'vx': random.uniform(-2, -0.5),
                'vy': random.uniform(-1, -0.3),
                'size': random.randint(4, 8),
                'start_frame': i,
                'lifetime': 8
            })

    for i in range(TOTAL_FRAMES):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Calculate animation phases
        leg_phase = i / 6  # Complete leg cycle every 6 frames
        tail_phase = i / 8
        bob_offset = 4 * math.sin(leg_phase * 2 * math.pi)
        arm_sway = math.sin(i / 4 * math.pi)
        ground_offset = i * 8  # Ground scrolls

        # Draw ground path
        draw_ground_path(draw, ground_offset, ground_y)

        # Draw dust
        draw_dust_puffs(draw, horse_x, ground_y, i, dust_particles)

        # Draw horse
        draw_horse(draw, horse_x, horse_y, leg_phase, tail_phase, int(bob_offset))

        # Draw rider
        draw_rider_on_horse(draw, horse_x, horse_y, int(bob_offset), arm_sway)

        frames.append(frame)

    return save_gif(frames, 'activites.gif')


# ============================================================================
# GIF 2: NAUTIQUE - Jet Ski with Rider
# ============================================================================

def draw_jetski_with_rider(draw, x, y, tilt=0, color=COLORS['navy'], fill=COLORS['green']):
    """Draw jet ski with rider."""
    # Jet ski body
    body_points = [
        (x - 50, y + 10),
        (x - 45, y - 15),
        (x - 20, y - 25),
        (x + 20, y - 30),
        (x + 50, y - 20),
        (x + 70, y - 10),
        (x + 75, y + 5),
        (x + 70, y + 15),
        (x - 50, y + 15),
    ]
    draw.polygon(body_points, fill=fill, outline=color, width=STROKE_WIDTH)

    # Handlebar
    draw.line([(x + 10, y - 30), (x + 20, y - 50)], fill=color, width=4)
    draw.line([(x + 10, y - 50), (x + 30, y - 50)], fill=color, width=4)

    # Rider
    rider_x = x - 10
    rider_y = y - 60

    # Head
    draw_stick_head(draw, rider_x, rider_y, radius=12, color=color)

    # Hair/scarf blowing back
    hair_points = [
        (rider_x - 5, rider_y - 8),
        (rider_x - 25, rider_y - 5 + tilt),
        (rider_x - 35, rider_y + tilt * 0.5),
    ]
    draw.line(hair_points, fill=color, width=3)

    # Body (leaning forward)
    body_angle = 30 + tilt * 2
    body_length = 40
    body_end_x = rider_x + body_length * math.sin(math.radians(body_angle))
    body_end_y = rider_y + 12 + body_length * math.cos(math.radians(body_angle))
    draw.line([(rider_x, rider_y + 12), (body_end_x, body_end_y)], fill=color, width=STROKE_WIDTH)

    # Arms reaching to handlebar
    draw.line([(rider_x + 5, rider_y + 20), (x + 20, y - 50)], fill=color, width=STROKE_WIDTH)
    draw.line([(rider_x + 5, rider_y + 20), (x + 10, y - 50)], fill=color, width=STROKE_WIDTH)

    # Legs
    draw.line([(body_end_x, body_end_y), (x + 30, y - 15)], fill=color, width=STROKE_WIDTH)
    draw.line([(body_end_x, body_end_y), (x - 20, y - 10)], fill=color, width=STROKE_WIDTH)


def draw_waves(draw, offset, y_base, color=COLORS['navy']):
    """Draw scrolling wave lines."""
    wave_amplitude = 8
    wave_length = 60

    for wave_idx, wave_y in enumerate([y_base, y_base + 25, y_base + 50]):
        points = []
        phase_offset = wave_idx * 0.3
        for px in range(-20, WIDTH + 40, 4):
            adjusted_x = px + offset
            py = wave_y + wave_amplitude * math.sin(2 * math.pi * adjusted_x / wave_length + phase_offset)
            points.append((px, py))

        if len(points) > 1:
            draw.line(points, fill=color, width=3)


def draw_splash_particles(draw, x, y, frame, particles):
    """Draw splash particles."""
    for p in particles:
        age = frame - p['start_frame']
        if 0 <= age < p['lifetime']:
            progress = age / p['lifetime']
            alpha = int(255 * (1 - progress))

            # Parabolic motion
            px = p['x'] + p['vx'] * age
            py = p['y'] + p['vy'] * age + 0.5 * age * age  # Gravity

            size = p['size'] * (1 - progress * 0.5)

            splash_color = COLORS['white'][:3] + (alpha,)
            draw.ellipse(
                [px - size, py - size, px + size, py + size],
                fill=splash_color
            )


def generate_nautique_gif():
    """Generate the nautique.gif - jet ski with rider."""
    print("Generating nautique.gif...")
    frames = []

    jetski_x = WIDTH // 2
    jetski_base_y = HEIGHT // 2 - 30
    wave_y = HEIGHT // 2 + 60

    # Pre-generate splash particles
    splash_particles = []
    for i in range(TOTAL_FRAMES):
        if i % 4 == 0:  # Regular splash
            for _ in range(3):
                splash_particles.append({
                    'x': jetski_x - 55 + random.randint(-5, 5),
                    'y': jetski_base_y + 20,
                    'vx': random.uniform(-3, -1),
                    'vy': random.uniform(-4, -2),
                    'size': random.randint(3, 6),
                    'start_frame': i,
                    'lifetime': 10
                })
        if i % 8 == 0:  # Big splash
            for _ in range(5):
                splash_particles.append({
                    'x': jetski_x - 55 + random.randint(-10, 10),
                    'y': jetski_base_y + 20,
                    'vx': random.uniform(-5, -2),
                    'vy': random.uniform(-6, -3),
                    'size': random.randint(5, 10),
                    'start_frame': i,
                    'lifetime': 12
                })

    for i in range(TOTAL_FRAMES):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Wave motion
        wave_phase = i / TOTAL_FRAMES * 2 * math.pi
        bounce = 8 * math.sin(wave_phase)
        tilt = 3 * math.sin(wave_phase)
        wave_offset = i * 10

        # Draw waves
        draw_waves(draw, wave_offset, wave_y)

        # Draw splash
        draw_splash_particles(draw, jetski_x, jetski_base_y, i, splash_particles)

        # Draw jet ski with rider
        draw_jetski_with_rider(draw, jetski_x, int(jetski_base_y + bounce), tilt)

        frames.append(frame)

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
    """Generate all GIF files."""
    print("=" * 60)
    print("Generating Service Type GIFs v2 with Stick Figures")
    print("=" * 60)
    print(f"Output directory: {OUTPUT_DIR}")
    print(f"Frame duration: {FRAME_DURATION}ms")
    print(f"Canvas size: {WIDTH}x{HEIGHT}px")
    print(f"Total frames: {TOTAL_FRAMES}")
    print()

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    generate_activites_gif()
    generate_nautique_gif()
    generate_hebergements_gif()
    generate_evenements_gif()

    print()
    print("=" * 60)
    print("All GIFs generated successfully!")
    print("=" * 60)


if __name__ == '__main__':
    main()
