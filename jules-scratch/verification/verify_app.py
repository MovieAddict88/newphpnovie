from playwright.sync_api import sync_playwright, expect

def run_verification(page):
    """
    Navigates the CineCraze app and takes screenshots for verification.
    """
    # Step 1: Go to the main page and verify it loads
    try:
        page.goto("http://localhost:8080/index.php", timeout=10000)
    except Exception as e:
        print(f"Error navigating to the page. Is the server running? {e}")
        # Read server log to help debug
        with open("jules-scratch/php_server.log", "r") as f:
            print("--- PHP Server Log ---")
            print(f.read())
        return

    # Step 2: Wait for the content grid to be populated and take a screenshot
    # We expect the grid to have at least one card.
    first_card = page.locator(".content-card").first
    expect(first_card).to_be_visible(timeout=10000)
    page.screenshot(path="jules-scratch/verification/main_page.png")
    print("Screenshot of the main page taken.")

    # Step 3: Navigate to the viewer page
    first_card.click()

    # Step 4: Wait for the viewer page to load by checking for the title
    details_title = page.locator("#details-title")
    # We expect the title not to be "Loading..." anymore.
    expect(details_title).not_to_have_text("Loading...", timeout=10000)
    print(f"Navigated to viewer page. Title: {details_title.inner_text()}")

    # Step 5: Take the final screenshot of the viewer page
    page.screenshot(path="jules-scratch/verification/verification.png")
    print("Screenshot of the viewer page taken. Verification complete.")


def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        run_verification(page)
        browser.close()

if __name__ == "__main__":
    main()
