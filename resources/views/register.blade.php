<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
</head>
<body>

    <h2>Sign Up</h2>

    @if(session('success'))
        <p style="color:green;">
            {{ session('success') }}
        </p>
    @endif

    @if($errors->any())
        <ul style="color:red;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('register') }}" method="POST">
        @csrf

        <table>
            <tr>
                <td>
                    <label>First Name</label><br>
                    <input
                        type="text"
                        name="first_name"
                        value="{{ old('first_name') }}"
                    >
                </td>

                <td>
                    <label>Surname</label><br>
                    <input
                        type="text"
                        name="surname"
                        value="{{ old('surname') }}"
                    >
                </td>
            </tr>

            <tr>
                <td>
                    <label>Username</label><br>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                    >
                </td>

                <td>
                    <label>Gender</label><br>
                    <select name="sex">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>
                            Male
                        </option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>
                            Female
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label>Address</label><br>
                    <input
                        type="text"
                        name="address"
                        value="{{ old('address') }}"
                    >
                </td>

                <td>
                    <label>Phone</label><br>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                    >
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label>Password</label><br>
                    <input
                        type="password"
                        name="password"
                    >
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label>Confirm Password</label><br>
                    <input
                        type="password"
                        name="password_confirmation"
                    >
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <button type="submit">
                        Create Account
                    </button>
                </td>
            </tr>

        </table>

    </form>

    <br>

    <p>
        Already have an account?
        <a href="{{ route('login') }}">Sign In</a>
    </p>

</body>
</html>