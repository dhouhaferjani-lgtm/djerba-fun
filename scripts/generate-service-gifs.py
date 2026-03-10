#!/usr/bin/env python3
"""
Generate animated GIF icons for Djerba Fun service type tiles.

This script creates 4 animated GIFs:
- tours.gif: Map pin with bouncing animation + dotted path
- jetski.gif: Jet ski bobbing on waves
- accommodation.gif: House drawing itself + palm tree swaying
- adventure.gif: Mountains with rising sun and waving flag

Usage: python3 scripts/generate-service-gifs.py
"""

import math
import os
from PIL import Image, ImageDraw

# ============================================================================
# Configuration
# ============================================================================

# Output directory
OUTPUT_DIR = os.path.join(os.path.dirname(__file__), '..', 'apps', 'web', 'public', 'images', 'experiences')

# Canvas size
WIDTH = 200
HEIGHT = 200

# Animation settings
FRAME_DURATION = 80  # milliseconds per frame
TOTAL_FRAMES = 18  # 15-20 frames

# Color palette from Djerba Fun design system
COLORS = {
    'navy': (27, 42, 78),           # #1B2A4E - primary stroke
    'emerald': (46, 158, 107),      # #2E9E6B - secondary fill
    'gold': (245, 176, 65),         # #F5B041 - accent
    'orange': (224, 93, 38),        # #E05D26 - tertiary
    'white': (255, 255, 255),       # for highlights
    'transparent': (0, 0, 0, 0),    # transparent background
}

# Stroke width
STROKE_WIDTH = 3


# ============================================================================
# Helper Functions
# ============================================================================

def create_frame():
    """Create a new transparent frame."""
    return Image.new('RGBA', (WIDTH, HEIGHT), COLORS['transparent'])


def ease_out_bounce(t):
    """Easing function for bounce effect (0 to 1)."""
    if t < 1/2.75:
        return 7.5625 * t * t
    elif t < 2/2.75:
        t -= 1.5/2.75
        return 7.5625 * t * t + 0.75
    elif t < 2.5/2.75:
        t -= 2.25/2.75
        return 7.5625 * t * t + 0.9375
    else:
        t -= 2.625/2.75
        return 7.5625 * t * t + 0.984375


def ease_out_cubic(t):
    """Easing function for smooth deceleration."""
    return 1 - pow(1 - t, 3)


def draw_thick_line(draw, start, end, color, width=STROKE_WIDTH):
    """Draw a line with specified width."""
    draw.line([start, end], fill=color, width=width)


def save_gif(frames, filename, duration=FRAME_DURATION):
    """Save frames as an animated GIF."""
    filepath = os.path.join(OUTPUT_DIR, filename)
    frames[0].save(
        filepath,
        save_all=True,
        append_images=frames[1:],
        duration=duration,
        loop=0,  # infinite loop
        disposal=2,  # clear frame before next
        transparency=0,
        optimize=True
    )
    file_size = os.path.getsize(filepath)
    print(f"  Saved: {filename} ({file_size / 1024:.1f} KB)")
    return filepath


# ============================================================================
# GIF 1: Tours (Map Pin + Dotted Path)
# ============================================================================

def draw_map_pin(draw, x, y, color=COLORS['navy'], fill=COLORS['emerald']):
    """Draw a location pin at position (x, y)."""
    pin_width = 30
    pin_height = 45

    # Pin body (teardrop shape using polygon)
    # Top circle center
    cx = x
    cy = y + 12
    radius = 12

    # Draw filled pin body
    points = []
    # Top semicircle
    for angle in range(180, 361):
        rad = math.radians(angle)
        px = cx + radius * math.cos(rad)
        py = cy + radius * math.sin(rad)
        points.append((px, py))
    # Bottom point
    points.append((x, y + pin_height - 5))
    # Complete the shape
    for angle in range(0, 181):
        rad = math.radians(angle)
        px = cx + radius * math.cos(rad)
        py = cy + radius * math.sin(rad)
        points.append((px, py))

    draw.polygon(points, fill=fill, outline=color, width=STROKE_WIDTH)

    # Inner circle (white dot)
    inner_radius = 5
    draw.ellipse(
        [cx - inner_radius, cy - inner_radius, cx + inner_radius, cy + inner_radius],
        fill=COLORS['white'],
        outline=color,
        width=2
    )


def draw_dotted_path(draw, y_pos, num_dots, color=COLORS['navy']):
    """Draw a dotted path with specified number of visible dots."""
    dot_spacing = 12
    dot_radius = 3
    start_x = 30

    for i in range(num_dots):
        x = start_x + i * dot_spacing
        if x > WIDTH - 30:
            break
        draw.ellipse(
            [x - dot_radius, y_pos - dot_radius, x + dot_radius, y_pos + dot_radius],
            fill=color
        )


def generate_tours_gif():
    """Generate the tours.gif animation."""
    print("Generating tours.gif...")
    frames = []

    pin_final_y = 70
    pin_start_y = -50
    path_y = 150
    max_dots = 12

    # Phase 1: Pin drops with bounce (frames 0-9)
    for i in range(10):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Calculate pin position with bounce easing
        t = i / 9
        ease = ease_out_bounce(t)
        pin_y = pin_start_y + (pin_final_y - pin_start_y) * ease

        draw_map_pin(draw, WIDTH // 2, int(pin_y))
        frames.append(frame)

    # Phase 2: Path draws (frames 10-15)
    for i in range(6):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        draw_map_pin(draw, WIDTH // 2, pin_final_y)

        # Progressive dot reveal
        num_dots = int((i + 1) / 6 * max_dots)
        draw_dotted_path(draw, path_y, num_dots)
        frames.append(frame)

    # Phase 3: Hold complete scene (frames 16-19)
    for i in range(4):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        draw_map_pin(draw, WIDTH // 2, pin_final_y)
        draw_dotted_path(draw, path_y, max_dots)
        frames.append(frame)

    return save_gif(frames, 'tours.gif')


# ============================================================================
# GIF 2: Jet Ski (Bobbing + Waves + Splashes)
# ============================================================================

def draw_jetski(draw, x, y, color=COLORS['navy'], fill=COLORS['emerald']):
    """Draw a simplified jet ski silhouette."""
    # Main body polygon
    body_points = [
        (x - 35, y + 5),      # back bottom
        (x - 30, y - 10),     # back top
        (x - 15, y - 15),     # seat back
        (x + 5, y - 20),      # handlebar area
        (x + 20, y - 15),     # front top
        (x + 40, y - 5),      # nose top
        (x + 45, y + 5),      # nose tip
        (x + 40, y + 10),     # nose bottom
        (x - 35, y + 10),     # back bottom
    ]
    draw.polygon(body_points, fill=fill, outline=color, width=STROKE_WIDTH)

    # Handlebar
    draw.line([(x + 5, y - 20), (x + 10, y - 30)], fill=color, width=STROKE_WIDTH)
    draw.line([(x + 5, y - 30), (x + 15, y - 30)], fill=color, width=STROKE_WIDTH)


def draw_waves(draw, offset, y_base, color=COLORS['navy']):
    """Draw scrolling wave lines."""
    wave_amplitude = 5
    wave_length = 40

    for wave_y in [y_base, y_base + 15, y_base + 30]:
        points = []
        for x in range(-20, WIDTH + 40, 3):
            adjusted_x = x + offset
            y = wave_y + wave_amplitude * math.sin(2 * math.pi * adjusted_x / wave_length)
            points.append((x, y))

        if len(points) > 1:
            draw.line(points, fill=color, width=2)


def draw_splash(draw, x, y, opacity, color=COLORS['white']):
    """Draw splash dots with variable opacity."""
    if opacity <= 0:
        return

    # Create color with opacity
    splash_color = color + (int(255 * opacity),)

    splash_positions = [
        (x - 5, y - 10),
        (x - 12, y - 5),
        (x - 8, y + 5),
        (x - 15, y - 15),
    ]

    for px, py in splash_positions:
        radius = 3
        draw.ellipse(
            [px - radius, py - radius, px + radius, py + radius],
            fill=splash_color
        )


def generate_jetski_gif():
    """Generate the jetski.gif animation."""
    print("Generating jetski.gif...")
    frames = []

    jetski_x = WIDTH // 2
    jetski_base_y = 80
    bob_amplitude = 4
    wave_speed = 8

    for i in range(TOTAL_FRAMES):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Calculate bobbing offset (sine wave)
        bob_offset = bob_amplitude * math.sin(2 * math.pi * i / TOTAL_FRAMES)
        jetski_y = jetski_base_y + bob_offset

        # Draw waves (scrolling right to left)
        wave_offset = -i * wave_speed
        draw_waves(draw, wave_offset, 120)

        # Draw jet ski
        draw_jetski(draw, jetski_x, int(jetski_y))

        # Draw splashes (cycling opacity)
        splash_phase = (i % 6) / 6
        splash_opacity = 0.7 * (1 - splash_phase) if splash_phase < 0.5 else 0
        draw_splash(draw, jetski_x - 45, int(jetski_y + 5), splash_opacity)

        frames.append(frame)

    return save_gif(frames, 'jetski.gif')


# ============================================================================
# GIF 3: Accommodation (House Drawing + Palm Tree + Stars)
# ============================================================================

def draw_house_progressive(draw, progress, color=COLORS['navy'], fill=COLORS['emerald']):
    """Draw house progressively based on progress (0 to 1)."""
    # House dimensions
    house_left = 50
    house_right = 130
    house_bottom = 160
    house_top = 100
    roof_peak = 60

    # Foundation (progress 0-0.15)
    if progress > 0:
        p = min(progress / 0.15, 1)
        draw.line(
            [(house_left, house_bottom), (house_left + (house_right - house_left) * p, house_bottom)],
            fill=color, width=STROKE_WIDTH
        )

    # Left wall (progress 0.15-0.30)
    if progress > 0.15:
        p = min((progress - 0.15) / 0.15, 1)
        draw.line(
            [(house_left, house_bottom), (house_left, house_bottom - (house_bottom - house_top) * p)],
            fill=color, width=STROKE_WIDTH
        )

    # Right wall (progress 0.30-0.45)
    if progress > 0.30:
        p = min((progress - 0.30) / 0.15, 1)
        draw.line(
            [(house_right, house_bottom), (house_right, house_bottom - (house_bottom - house_top) * p)],
            fill=color, width=STROKE_WIDTH
        )

    # Roof left (progress 0.45-0.60)
    if progress > 0.45:
        p = min((progress - 0.45) / 0.15, 1)
        mid_x = (house_left + house_right) / 2
        draw.line(
            [(house_left, house_top),
             (house_left + (mid_x - house_left) * p, house_top - (house_top - roof_peak) * p)],
            fill=color, width=STROKE_WIDTH
        )

    # Roof right (progress 0.60-0.75)
    if progress > 0.60:
        p = min((progress - 0.60) / 0.15, 1)
        mid_x = (house_left + house_right) / 2
        draw.line(
            [(mid_x, roof_peak),
             (mid_x + (house_right - mid_x) * p, roof_peak + (house_top - roof_peak) * p)],
            fill=color, width=STROKE_WIDTH
        )

    # Door (progress 0.75-0.85)
    if progress > 0.75:
        p = min((progress - 0.75) / 0.10, 1)
        door_left = 80
        door_right = 100
        door_top = 125
        door_height = (house_bottom - door_top) * p
        draw.rectangle(
            [(door_left, house_bottom - door_height), (door_right, house_bottom)],
            fill=fill, outline=color, width=2
        )

    # Windows (progress 0.85-1.0)
    if progress > 0.85:
        p = min((progress - 0.85) / 0.15, 1)
        window_size = int(15 * p)
        if window_size > 0:
            # Left window
            draw.rectangle(
                [(58, 115), (58 + window_size, 115 + window_size)],
                fill=COLORS['gold'], outline=color, width=2
            )
            # Right window
            draw.rectangle(
                [(107, 115), (107 + window_size, 115 + window_size)],
                fill=COLORS['gold'], outline=color, width=2
            )


def draw_palm_tree(draw, x, y, sway_angle, color=COLORS['navy'], leaf_color=COLORS['emerald']):
    """Draw palm tree with swaying leaves."""
    # Trunk
    trunk_points = [
        (x - 5, y),
        (x - 3, y - 40),
        (x + 3, y - 40),
        (x + 5, y),
    ]
    draw.polygon(trunk_points, fill=COLORS['orange'], outline=color, width=1)

    # Palm leaves (3 leaves with sway)
    leaf_length = 25
    base_angles = [-60, -30, 0, 30, 60]

    for base_angle in base_angles:
        angle = math.radians(base_angle + sway_angle - 90)
        end_x = x + leaf_length * math.cos(angle)
        end_y = (y - 40) + leaf_length * math.sin(angle)

        # Draw curved leaf
        mid_x = x + (leaf_length * 0.6) * math.cos(angle)
        mid_y = (y - 40) + (leaf_length * 0.6) * math.sin(angle)

        draw.line([(x, y - 40), (int(end_x), int(end_y))], fill=leaf_color, width=3)


def draw_moon_and_stars(draw, opacity, twinkle_phase):
    """Draw moon and twinkling stars."""
    if opacity <= 0:
        return

    # Moon
    moon_color = COLORS['gold'][:3] + (int(255 * opacity),)
    moon_x, moon_y = 45, 35
    moon_radius = 10
    draw.ellipse(
        [moon_x - moon_radius, moon_y - moon_radius,
         moon_x + moon_radius, moon_y + moon_radius],
        fill=moon_color
    )

    # Stars
    star_positions = [(25, 50), (60, 25)]
    for i, (sx, sy) in enumerate(star_positions):
        # Twinkle effect
        star_opacity = opacity * (0.5 + 0.5 * math.sin(twinkle_phase + i * math.pi))
        star_color = COLORS['gold'][:3] + (int(255 * star_opacity),)

        # Draw star as small cross
        size = 4
        draw.line([(sx - size, sy), (sx + size, sy)], fill=star_color, width=2)
        draw.line([(sx, sy - size), (sx, sy + size)], fill=star_color, width=2)


def generate_accommodation_gif():
    """Generate the accommodation.gif animation."""
    print("Generating accommodation.gif...")
    frames = []

    palm_x = 160
    palm_y = 160

    # Phase 1: House draws (frames 0-11)
    for i in range(12):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        progress = (i + 1) / 12
        draw_house_progressive(draw, progress)

        frames.append(frame)

    # Phase 2: Palm tree appears and sways, moon/stars fade in (frames 12-17)
    for i in range(6):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Complete house
        draw_house_progressive(draw, 1.0)

        # Swaying palm tree
        sway_angle = 5 * math.sin(2 * math.pi * i / 6)
        draw_palm_tree(draw, palm_x, palm_y, sway_angle)

        # Fading in moon and stars
        opacity = (i + 1) / 6
        twinkle_phase = 2 * math.pi * i / 6
        draw_moon_and_stars(draw, opacity, twinkle_phase)

        frames.append(frame)

    # Phase 3: Hold with twinkling (frames 18-21) - extended for smooth loop
    for i in range(4):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        draw_house_progressive(draw, 1.0)

        sway_angle = 5 * math.sin(2 * math.pi * (6 + i) / 6)
        draw_palm_tree(draw, palm_x, palm_y, sway_angle)

        twinkle_phase = 2 * math.pi * (6 + i) / 6
        draw_moon_and_stars(draw, 1.0, twinkle_phase)

        frames.append(frame)

    return save_gif(frames, 'accommodation.gif')


# ============================================================================
# GIF 4: Adventure (Mountains + Rising Sun + Flag)
# ============================================================================

def draw_mountains(draw, color=COLORS['navy'], fill=COLORS['emerald']):
    """Draw mountain silhouettes."""
    # Back mountain (smaller, lighter)
    back_mountain = [
        (20, 160),
        (80, 80),
        (140, 160),
    ]
    # Use a lighter shade for back mountain
    back_fill = (46, 158, 107, 180)  # emerald with transparency
    draw.polygon(back_mountain, fill=back_fill, outline=color, width=2)

    # Front mountain (larger)
    front_mountain = [
        (60, 160),
        (130, 55),
        (200, 160),
    ]
    draw.polygon(front_mountain, fill=fill, outline=color, width=STROKE_WIDTH)

    # Small peak on right
    small_peak = [
        (140, 160),
        (175, 100),
        (210, 160),
    ]
    draw.polygon(small_peak, fill=back_fill, outline=color, width=2)


def draw_sun(draw, y_offset, ray_length, color=COLORS['gold']):
    """Draw sun with rays at given vertical position."""
    sun_x = 130
    sun_base_y = 55  # Behind the tallest peak
    sun_y = sun_base_y - y_offset
    sun_radius = 15

    # Only draw if sun is visible (above a certain point)
    if sun_y < 80:
        # Sun circle
        draw.ellipse(
            [sun_x - sun_radius, sun_y - sun_radius,
             sun_x + sun_radius, sun_y + sun_radius],
            fill=color
        )

        # Sun rays (6 rays)
        if ray_length > 0:
            for i in range(6):
                angle = math.radians(i * 60)
                start_x = sun_x + (sun_radius + 3) * math.cos(angle)
                start_y = sun_y + (sun_radius + 3) * math.sin(angle)
                end_x = sun_x + (sun_radius + 3 + ray_length) * math.cos(angle)
                end_y = sun_y + (sun_radius + 3 + ray_length) * math.sin(angle)
                draw.line([(start_x, start_y), (end_x, end_y)], fill=color, width=2)


def draw_flag(draw, wave_offset, color=COLORS['orange']):
    """Draw waving flag on mountain peak."""
    pole_x = 130
    pole_top = 35
    pole_bottom = 55

    # Flag pole
    draw.line([(pole_x, pole_bottom), (pole_x, pole_top)], fill=COLORS['navy'], width=2)

    # Waving flag
    flag_width = 20
    flag_height = 12

    # Create wavy flag shape
    wave = 3 * math.sin(wave_offset)
    flag_points = [
        (pole_x, pole_top),
        (pole_x + flag_width * 0.5, pole_top + wave),
        (pole_x + flag_width, pole_top + flag_height * 0.5 + wave * 0.5),
        (pole_x + flag_width * 0.5, pole_top + flag_height + wave),
        (pole_x, pole_top + flag_height),
    ]
    draw.polygon(flag_points, fill=color, outline=COLORS['navy'], width=1)


def generate_adventure_gif():
    """Generate the adventure.gif animation."""
    print("Generating adventure.gif...")
    frames = []

    max_sun_rise = 35
    max_ray_length = 12

    # Phase 1: Sun rises (frames 0-9)
    for i in range(10):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        # Draw mountains first (sun will be partially behind)
        draw_mountains(draw)

        # Rising sun
        progress = ease_out_cubic(i / 9)
        sun_offset = progress * max_sun_rise
        draw_sun(draw, sun_offset, 0)

        # Static flag
        draw_flag(draw, 0)

        frames.append(frame)

    # Phase 2: Rays extend (frames 10-13)
    for i in range(4):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        draw_mountains(draw)

        # Full sun position, growing rays
        ray_progress = (i + 1) / 4
        draw_sun(draw, max_sun_rise, int(max_ray_length * ray_progress))

        # Waving flag
        wave_offset = 2 * math.pi * i / 4
        draw_flag(draw, wave_offset)

        frames.append(frame)

    # Phase 3: Hold complete scene with waving flag (frames 14-17)
    for i in range(4):
        frame = create_frame()
        draw = ImageDraw.Draw(frame)

        draw_mountains(draw)
        draw_sun(draw, max_sun_rise, max_ray_length)

        wave_offset = 2 * math.pi * (4 + i) / 4
        draw_flag(draw, wave_offset)

        frames.append(frame)

    return save_gif(frames, 'adventure.gif')


# ============================================================================
# Main
# ============================================================================

def main():
    """Generate all GIF files."""
    print("=" * 60)
    print("Generating Service Type GIFs for Djerba Fun")
    print("=" * 60)
    print(f"Output directory: {OUTPUT_DIR}")
    print(f"Frame duration: {FRAME_DURATION}ms")
    print(f"Canvas size: {WIDTH}x{HEIGHT}px")
    print()

    # Ensure output directory exists
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    # Generate all GIFs
    generate_tours_gif()
    generate_jetski_gif()
    generate_accommodation_gif()
    generate_adventure_gif()

    print()
    print("=" * 60)
    print("All GIFs generated successfully!")
    print("=" * 60)


if __name__ == '__main__':
    main()
