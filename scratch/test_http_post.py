import urllib.request
import json
import re

# 1. Fetch checkout.php to get CSRF token and session cookie
req = urllib.request.Request('http://localhost/gawdee/checkout.php')
with urllib.request.urlopen(req) as resp:
    cookies = resp.headers.get('Set-Cookie')
    html = resp.read().decode('utf-8')
    match = re.search(r'data-csrf="([^"]+)"', html)
    csrf_token = match.group(1) if match else ''

print(f"CSRF Token: {csrf_token}")
print(f"Cookie: {cookies}")

# 2. Submit order payload via POST to api/create-order.php
payload = {
    'csrf_token': csrf_token,
    'checkout_token': 'test_token_py_' + str(int(urllib.request.time.time())),
    'coupon_code': '',
    'customer': {
        'name': 'Jane Doe',
        'email': 'jane.doe@example.com',
        'phone': '9876543210',
        'address1': '456 Green Road',
        'address2': 'Suite 101',
        'city': 'Bengaluru',
        'state': 'Karnataka',
        'pincode': '560001',
        'notes': 'Call on arrival'
    },
    'payment_method': 'cod',
    'items': [
        {'id': 'forest-honey', 'quantity': 1}
    ]
}

data = json.dumps(payload).encode('utf-8')
post_req = urllib.request.Request(
    'http://localhost/gawdee/api/create-order.php',
    data=data,
    headers={
        'Content-Type': 'application/json',
        'Cookie': cookies
    },
    method='POST'
)

with urllib.request.urlopen(post_req) as post_resp:
    res_text = post_resp.read().decode('utf-8')
    print("HTTP Response Code:", post_resp.status)
    print("Response Body:", res_text)
