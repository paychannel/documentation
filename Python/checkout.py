from collections import defaultdict
import binascii
from hashlib import md5


def get_signature(params, secret_key):
    """
    Base64(Byte(MD5(Windows1251(Sort(Params) + SecretKey))))
    params - list of tuples [('RDI_CURRENCY_ID', 643), ('RDI_PAYMENT_AMOUNT', 10)]
    """
    icase_key = lambda s: unicode(s).lower()
 
    lists_by_keys = defaultdict(list)
    for key, value in params:
        lists_by_keys[key].append(value)
 
    str_buff = ''
    for key in sorted(lists_by_keys, key=icase_key):
        for value in sorted(lists_by_keys[key], key=icase_key):
            str_buff += unicode(value).encode('1251')
    str_buff += secret_key
    md5_string = md5(str_buff).digest()
    return binascii.b2a_base64(md5_string)[:-1]
